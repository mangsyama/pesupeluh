<?php

namespace Tests\Feature;

use App\Models\IssueCategory;
use App\Models\Role;
use App\Models\Room;
use App\Models\ServiceTicket;
use App\Models\SupportingUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OffHoursDispositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_created_off_hours_assigns_all_active_unit_technicians()
    {
        // 1. Setup supporting unit, room, category
        $unit = SupportingUnit::create(['name' => 'IPSRS Unit Test', 'slug' => 'ipsrs-unit-test']);
        $room = Room::create(['name' => 'Ruang 101', 'building_name' => 'Gedung A', 'location_floor' => '1']);
        $category = IssueCategory::create(['name' => 'AC Rusak', 'supporting_unit_id' => $unit->id]);

        // 2. Setup reporter user
        $reporter = User::factory()->create(['role_id' => Role::PJ_RUANGAN, 'is_active' => true]);

        // 3. Setup multiple active technicians for unit
        $tech1 = User::factory()->create([
            'role_id' => Role::TEKNISI,
            'supporting_unit_id' => $unit->id,
            'is_active' => true,
            'name' => 'Teknisi 1'
        ]);

        $tech2 = User::factory()->create([
            'role_id' => Role::TEKNISI,
            'supporting_unit_id' => $unit->id,
            'is_active' => true,
            'name' => 'Teknisi 2'
        ]);

        $tech3 = User::factory()->create([
            'role_id' => Role::TEKNISI,
            'supporting_unit_id' => $unit->id,
            'is_active' => true,
            'name' => 'Teknisi 3'
        ]);

        // 4. Force Off-Hours time (Sunday 22:00 WITA)
        \Carbon\Carbon::setTestNow('2026-08-16 22:00:00');

        // Submit ticket as reporter
        $response = $this->actingAs($reporter)->post(route('services.tickets.store'), [
            'room_id' => $room->id,
            'category_id' => $category->id,
            'problem_description' => 'Test perbaikan pendingin ruangan AC',
            'priority' => 'ROUTINE',
            'attachments' => [
                'data:image/png;base64,iVBORw0KGgoAAAANSU5EUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
            ],
        ]);

        $ticket = ServiceTicket::latest('id')->first();
        $this->assertNotNull($ticket);

        // Check assigned technicians count
        $assignments = $ticket->assignments;
        
        // If off-hours, all 3 technicians must be assigned
        if (\App\Services\UnitWorkingHourService::isOffHours($unit->id)) {
            $this->assertEquals('ASSIGNED', $ticket->status);
            $this->assertCount(3, $assignments);
            $this->assertTrue($assignments->pluck('technician_id')->contains($tech1->id));
            $this->assertTrue($assignments->pluck('technician_id')->contains($tech2->id));
            $this->assertTrue($assignments->pluck('technician_id')->contains($tech3->id));
        } else {
            $this->assertEquals('PENDING_VALIDATION', $ticket->status);
        }
    }
}
