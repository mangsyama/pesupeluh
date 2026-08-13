<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\SupportingUnit;
use App\Models\UnitWorkingHour;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TechnicianPositionController extends Controller
{
    /**
     * Display live technician position & availability status.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Query technicians with their in-progress ticket assignments and location
        $technicians = User::where('role_id', Role::TEKNISI) // TECHNICIAN
            ->where('is_active', 1)
            ->with(['supportingUnit:id,name,slug'])
            ->with(['assignments' => function ($query) {
                $query->whereHas('ticket', function ($q) {
                    $q->whereIn('status', ['ASSIGNED', 'IN_PROGRESS']);
                })->with(['ticket:id,uuid,ticket_number,problem_description,status,room_id', 'ticket.room:id,name,building_name,location_floor']);
            }])
            ->withCount([
                'assignments as active_tickets_count' => function ($query) {
                    $query->whereHas('ticket', function ($q) {
                        $q->whereIn('status', ['ASSIGNED', 'IN_PROGRESS']);
                    });
                },
                'assignments as total_tickets_count' => function ($query) {
                    $query->whereHas('ticket');
                }
            ])
            ->orderBy('is_on_duty', 'desc')
            ->get(['id', 'name', 'nip', 'phone_number', 'supporting_unit_id', 'is_on_duty', 'duty_status', 'specialties', 'current_location', 'profile_photo_path']);

        // Transform technicians: automatically compute status and location from actual assignments
        $transformedTechnicians = $technicians->map(function ($t) {
            $activeAssignment = $t->assignments->first();
            $activeTicket = $activeAssignment ? $activeAssignment->ticket : null;

            // Determine duty status: if offline/not on duty, mark OFF. Otherwise BUSY if active ticket, else READY.
            $computedDutyStatus = !$t->is_on_duty ? 'OFF' : (($t->active_tickets_count > 0) ? 'BUSY' : 'READY');

            // Automatically determine location from active ticket room or fallback
            $autoLocation = null;
            if ($activeTicket) {
                $roomName = $activeTicket->room ? $activeTicket->room->name : null;
                $locParts = array_filter([$roomName]);
                $autoLocation = !empty($locParts) ? implode(' - ', $locParts) : 'Lokasi Penugasan';
            } else {
                $autoLocation = $t->current_location ?: 'Area Pos Standby';
            }

            return [
                'id' => $t->id,
                'name' => $t->name,
                'nip' => $t->nip,
                'phone_number' => $t->phone_number,
                'supporting_unit_id' => $t->supporting_unit_id,
                'supporting_unit' => $t->supportingUnit,
                'active_tickets_count' => $t->active_tickets_count,
                'total_tickets_count' => $t->total_tickets_count,
                'duty_status' => $computedDutyStatus,
                'is_on_duty' => (bool) $t->is_on_duty,
                'current_location' => $autoLocation,
                'active_ticket' => $activeTicket ? [
                    'uuid' => $activeTicket->uuid,
                    'ticket_number' => $activeTicket->ticket_number,
                    'title' => $activeTicket->problem_description,
                    'status' => $activeTicket->status,
                    'room_name' => $activeTicket->room ? $activeTicket->room->name : null,
                ] : null,
            ];
        });

        $units = SupportingUnit::where('status', 'ACTIVE')->get(['id', 'name', 'slug']);

        if ($request->wantsJson()) {
            return response()->json([
                'technicians' => $transformedTechnicians,
                'units' => $units,
            ]);
        }

        return Inertia::render('TechnicianPosition/Index', [
            'technicians' => $transformedTechnicians,
            'units' => $units,
            'currentUser' => [
                'id' => $user->id,
                'role_id' => $user->role_id,
            ]
        ]);
    }

    /**
     * Display unit working hours management.
     */
    public function indexWorkingHours(Request $request)
    {
        $user = $request->user();
        $units = SupportingUnit::where('status', 'ACTIVE')->get(['id', 'name', 'slug']);
        $workingHours = UnitWorkingHour::with('supportingUnit:id,name')->get();

        return Inertia::render('WorkingHours/Index', [
            'units' => $units,
            'workingHours' => $workingHours,
            'currentUser' => [
                'id' => $user->id,
                'role_id' => $user->role_id,
                'name' => $user->name,
                'supporting_unit_id' => $user->supporting_unit_id,
            ]
        ]);
    }

    /**
     * Update current technician duty status & location.
     */
    public function updateStatus(Request $request)
    {
        $user = $request->user();

        // Allow Technician, Admin, or Disposisi roles to update status
        if (!$user->isTechnician() && !$user->isAdmin() && !$user->canDisposisi()) {
            abort(403, 'Anda tidak memiliki hak akses untuk merubah status teknisi.');
        }

        $validated = $request->validate([
            'technician_id' => 'nullable|exists:users,id',
            'is_on_duty' => 'required|boolean',
            'duty_status' => 'required|in:READY,BUSY,OFF',
            'current_location' => 'nullable|string|max:150',
            'specialties' => 'nullable|array',
        ]);

        $targetUser = $user;
        if (!empty($validated['technician_id']) && ($user->isAdmin() || $user->canDisposisi())) {
            $targetUser = User::findOrFail($validated['technician_id']);
        }

        $targetUser->update([
            'is_on_duty' => $validated['is_on_duty'],
            'duty_status' => $validated['duty_status'],
            'current_location' => $validated['current_location'] ?? $targetUser->current_location,
            'specialties' => $validated['specialties'] ?? $targetUser->specialties,
        ]);

        return back()->with('success', 'Status & ketersediaan teknisi berhasil diperbarui.');
    }

    /**
     * Save/update working hours configuration (Admin / Kepala Instalasi / Disposisi)
     */
    public function updateWorkingHours(Request $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$user->canDisposisi()) {
            abort(403, 'Hanya Admin atau Kepala Instalasi yang dapat mengubah jam operasional.');
        }

        $validated = $request->validate([
            'supporting_unit_id' => 'required|exists:supporting_units,id',
            'hours' => 'required|array|min:1',
            'hours.*.day_of_week' => 'required|integer|between:1,7',
            'hours.*.start_time' => 'required|date_format:H:i',
            'hours.*.end_time' => 'required|date_format:H:i',
            'hours.*.is_active' => 'required|boolean',
        ]);

        // Disposisi role can only edit working hours of their own unit
        if (!$user->isAdmin() && (int) $user->supporting_unit_id !== (int) $validated['supporting_unit_id']) {
            abort(403, 'Anda hanya dapat mengubah jam operasional unit penunjang Anda sendiri.');
        }

        foreach ($validated['hours'] as $h) {
            UnitWorkingHour::updateOrCreate(
                [
                    'supporting_unit_id' => $validated['supporting_unit_id'],
                    'day_of_week' => $h['day_of_week'],
                ],
                [
                    'start_time' => $h['start_time'] . ':00',
                    'end_time' => $h['end_time'] . ':00',
                    'is_active' => $h['is_active'],
                ]
            );
        }

        return back()->with('success', 'Jam operasional unit berhasil diperbarui.');
    }
}
