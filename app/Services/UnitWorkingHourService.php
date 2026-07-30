<?php

namespace App\Services;

use App\Models\UnitWorkingHour;
use Carbon\Carbon;

class UnitWorkingHourService
{
    /**
     * Determine if a supporting unit is currently in off-hours.
     *
     * @param int|null $supportingUnitId
     * @param Carbon|null $dateTime
     * @return bool
     */
    public static function isOffHours(?int $supportingUnitId, ?Carbon $dateTime = null): bool
    {
        if (!$supportingUnitId) {
            return false;
        }

        $tz = config('app.timezone', 'Asia/Jakarta');
        $now = ($dateTime ?? now())->setTimezone($tz);
        $currentTime = $now->format('H:i:s');
        $dayOfWeek = $now->dayOfWeekIso; // 1=Monday, 7=Sunday

        $workingHour = UnitWorkingHour::where('supporting_unit_id', $supportingUnitId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if ($workingHour) {
            if (!$workingHour->is_active) {
                // Unit set as inactive/off on this day
                return true;
            }

            // Clean time string to HH:MM:SS format
            $startTime = substr((string) $workingHour->start_time, 0, 8);
            $endTime = substr((string) $workingHour->end_time, 0, 8);

            return ($currentTime < $startTime || $currentTime > $endTime);
        }

        // Default operational rule if no specific schedule configured: Monday-Friday 07:30 - 15:00
        return ($dayOfWeek >= 6 || $currentTime < '07:30:00' || $currentTime > '15:00:00');
    }

    /**
     * Determine if a supporting unit is currently in operational (working) hours.
     *
     * @param int|null $supportingUnitId
     * @param Carbon|null $dateTime
     * @return bool
     */
    public static function isOperationalHours(?int $supportingUnitId, ?Carbon $dateTime = null): bool
    {
        return !self::isOffHours($supportingUnitId, $dateTime);
    }
}
