<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DashboardAnalyticsService
{
    /**
     * Menghitung seluruh metrik performa eksekutif, SLA, tren harian, dan distribusi status
     * untuk berbagai pilihan periode (7 Hari, 14 Hari, 30 Hari / 1 Bulan, Semua Data).
     *
     * @param Builder|\Illuminate\Database\Query\Builder $query
     * @return array
     */
    public static function getAnalytics($query): array
    {
        // Clone query agar tidak merusak base query utama
        $allTickets = (clone $query)->select([
            'id', 'status', 'created_at', 'validated_at', 'validated_by',
            'responded_at', 'resolved_at', 'updated_at', 'paused_duration_seconds'
        ])->get();

        $periods = [
            '7' => static::calculatePeriodMetrics($allTickets, 7, '7 Hari'),
            '14' => static::calculatePeriodMetrics($allTickets, 14, '14 Hari'),
            '30' => static::calculatePeriodMetrics($allTickets, 30, '1 Bulan'),
            '60' => static::calculatePeriodMetrics($allTickets, null, 'Semua Data'),
        ];

        $defaultPeriod = '30';

        return [
            'periods' => $periods,
            'activePeriod' => $defaultPeriod,
            // Fallback flat properties for backwards compatibility
            'kpi' => $periods[$defaultPeriod]['kpi'],
            'trendData' => $periods[$defaultPeriod]['trendData'],
            'trendPeriods' => [
                '7' => ['label' => '7 Hari', 'days' => 7, 'data' => $periods['7']['trendData']],
                '14' => ['label' => '14 Hari', 'days' => 14, 'data' => $periods['14']['trendData']],
                '30' => ['label' => '30 Hari (1 Bulan)', 'days' => 30, 'data' => $periods['30']['trendData']],
                '60' => ['label' => 'Semua (60 Hari)', 'days' => 60, 'data' => $periods['60']['trendData']],
            ],
            'statusDistribution' => $periods[$defaultPeriod]['statusDistribution'],
        ];
    }

    /**
     * Hitung seluruh metrik SLA, KPI, distribusi, dan tren untuk tiket dalam rentang hari tertentu.
     *
     * @param \Illuminate\Support\Collection $allTickets
     * @param int|null $days
     * @param string $label
     * @return array
     */
    public static function calculatePeriodMetrics($allTickets, ?int $days, string $label): array
    {
        if ($days !== null) {
            $startDate = Carbon::today()->subDays($days - 1)->startOfDay();
            $tickets = $allTickets->filter(function ($t) use ($startDate) {
                return $t->created_at && $t->created_at >= $startDate;
            });
            $trendDays = $days;
        } else {
            $tickets = $allTickets;
            $trendDays = 60;
        }

        $totalCount = $tickets->count();

        // 1. Metrik Disposisi Sistem vs Manual
        $autoDisposedCount = $tickets->filter(fn($t) => !is_null($t->validated_at) && is_null($t->validated_by))->count();
        $manualDisposedCount = $tickets->filter(fn($t) => !is_null($t->validated_at) && !is_null($t->validated_by))->count();
        $pendingValidationCount = $tickets->where('status', 'PENDING_VALIDATION')->count();
        $totalDisposed = $autoDisposedCount + $manualDisposedCount;
        $autoDisposedRate = $totalDisposed > 0 ? round(($autoDisposedCount / $totalDisposed) * 100) : 0;
        $manualDisposedRate = $totalDisposed > 0 ? round(($manualDisposedCount / $totalDisposed) * 100) : 0;

        // 2. Metrik Waktu Respon Teknisi (Menit) & SLA
        $respondedTickets = $tickets->filter(fn($t) => !is_null($t->responded_at));
        $avgResponseMinutes = 0;
        $slaCompliantCount = 0;

        if ($respondedTickets->isNotEmpty()) {
            $totalResponseMinutes = 0;
            foreach ($respondedTickets as $ticket) {
                $startTime = $ticket->validated_at ?? $ticket->created_at;
                $diff = max(1, $startTime->diffInMinutes($ticket->responded_at));
                $totalResponseMinutes += $diff;
                if ($diff <= 30) {
                    $slaCompliantCount++;
                }
            }
            $avgResponseMinutes = round($totalResponseMinutes / $respondedTickets->count());
        }

        $slaComplianceRate = $respondedTickets->isNotEmpty() 
            ? round(($slaCompliantCount / $respondedTickets->count()) * 100)
            : 100;

        // 3. Metrik Waktu Penyelesaian (Jam)
        $completedTickets = $tickets->where('status', 'COMPLETED');
        $avgResolutionHours = 0;
        if ($completedTickets->isNotEmpty()) {
            $totalResolutionHours = 0;
            foreach ($completedTickets as $ticket) {
                $finishTime = $ticket->resolved_at ?? $ticket->updated_at;
                $startTime = $ticket->created_at;
                $pausedMinutes = (int) (($ticket->paused_duration_seconds ?? 0) / 60);
                $diffHours = max(0.1, ($startTime->diffInMinutes($finishTime) - $pausedMinutes) / 60);
                $totalResolutionHours += $diffHours;
            }
            $avgResolutionHours = round($totalResolutionHours / $completedTickets->count(), 1);
        }

        // 4. Distribusi Status Tiket
        $statusDistribution = [
            'COMPLETED' => $tickets->where('status', 'COMPLETED')->count(),
            'IN_PROGRESS' => $tickets->where('status', 'IN_PROGRESS')->count(),
            'ASSIGNED' => $tickets->where('status', 'ASSIGNED')->count(),
            'PENDING' => $tickets->where('status', 'PENDING')->count(),
            'PENDING_VALIDATION' => $pendingValidationCount,
            'CANCEL' => $tickets->where('status', 'CANCEL')->count(),
        ];

        // 5. Tren Harian
        $trendData = static::generateTrendData($allTickets, $trendDays);

        return [
            'label' => $label,
            'days' => $days,
            'kpi' => [
                'avgResponseMinutes' => $avgResponseMinutes,
                'avgResolutionHours' => $avgResolutionHours,
                'slaComplianceRate' => $slaComplianceRate,
                'autoDisposedCount' => $autoDisposedCount,
                'manualDisposedCount' => $manualDisposedCount,
                'pendingValidationCount' => $pendingValidationCount,
                'autoDisposedRate' => $autoDisposedRate,
                'manualDisposedRate' => $manualDisposedRate,
                'totalTickets' => $totalCount,
                'activeTickets' => $tickets->whereIn('status', ['ASSIGNED', 'IN_PROGRESS', 'PENDING'])->count(),
                'completedTickets' => $completedTickets->count(),
            ],
            'statusDistribution' => $statusDistribution,
            'trendData' => $trendData,
        ];
    }

    /**
     * Generate dataset tren harian untuk periode hari yang ditentukan.
     *
     * @param \Illuminate\Support\Collection $tickets
     * @param int $days
     * @return array
     */
    public static function generateTrendData($tickets, int $days): array
    {
        $incomingGrouped = [];
        $completedGrouped = [];

        foreach ($tickets as $t) {
            if ($t->created_at) {
                $cDate = $t->created_at->toDateString();
                $incomingGrouped[$cDate] = ($incomingGrouped[$cDate] ?? 0) + 1;
            }
            $doneDate = $t->resolved_at ?? ($t->status === 'COMPLETED' ? $t->updated_at : null);
            if ($doneDate) {
                $dDate = $doneDate->toDateString();
                $completedGrouped[$dDate] = ($completedGrouped[$dDate] ?? 0) + 1;
            }
        }

        $trendData = [];
        $startDate = Carbon::today()->subDays($days - 1);

        for ($i = 0; $i < $days; $i++) {
            $currentDate = (clone $startDate)->addDays($i);
            $dateStr = $currentDate->toDateString();
            $label = $currentDate->translatedFormat('d M');

            $trendData[] = [
                'date' => $dateStr,
                'label' => $label,
                'incoming' => $incomingGrouped[$dateStr] ?? 0,
                'completed' => $completedGrouped[$dateStr] ?? 0,
            ];
        }

        return $trendData;
    }
}
