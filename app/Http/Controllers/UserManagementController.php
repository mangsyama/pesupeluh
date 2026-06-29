<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Room;
use App\Models\SupportingUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    /**
     * Display a listing of user registration approval requests.
     */
    public function indexApprovals()
    {
        $users = User::with(['role', 'room', 'supportingUnit'])
            ->whereNull('approved_by')
            ->orderBy('id', 'desc')
            ->get();

        $roles = Role::orderBy('id', 'asc')->get();
        $rooms = Room::orderBy('name', 'asc')->get();
        $supportingUnits = SupportingUnit::orderBy('name', 'asc')->get();

        return Inertia::render('UserManagement/Approvals', [
            'users' => $users,
            'roles' => $roles,
            'rooms' => $rooms,
            'supportingUnits' => $supportingUnits,
        ]);
    }

    /**
     * Approve and activate a pending user registration.
     */
    public function approveUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'room_id' => 'nullable|exists:rooms,id',
            'supporting_unit_id' => 'nullable|exists:supporting_units,id',
        ]);

        $user->update([
            'role_id' => $validated['role_id'],
            'room_id' => $validated['room_id'],
            'supporting_unit_id' => $validated['supporting_unit_id'],
            'is_active' => true,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pendaftaran user berhasil disetujui.');
    }

    /**
     * Display a listing of admin users.
     */
    public function indexAdmin()
    {
        $users = User::with(['role'])
            ->where('role_id', 1)
            ->whereNotNull('approved_by')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('UserManagement/Admin', [
            'users' => $users,
        ]);
    }

    /**
     * Display a listing of management users.
     */
    public function indexManagement()
    {
        $users = User::with(['role'])
            ->whereIn('role_id', [2, 3, 4])
            ->whereNotNull('approved_by')
            ->orderBy('id', 'desc')
            ->get();

        $managementRoles = Role::whereIn('id', [2, 3, 4])->orderBy('id', 'asc')->get();

        return Inertia::render('UserManagement/Management', [
            'users' => $users,
            'managementRoles' => $managementRoles,
        ]);
    }

    /**
     * Display a listing of unit head users.
     */
    public function indexUnitHead()
    {
        $users = User::with(['role', 'room', 'supportingUnit'])
            ->where('role_id', 5)
            ->whereNotNull('approved_by')
            ->orderBy('id', 'desc')
            ->get();

        $rooms = Room::orderBy('name', 'asc')->get();
        $supportingUnits = SupportingUnit::orderBy('name', 'asc')->get();

        return Inertia::render('UserManagement/UnitHead', [
            'users' => $users,
            'rooms' => $rooms,
            'supportingUnits' => $supportingUnits,
        ]);
    }

    /**
     * Display a listing of technician users.
     */
    public function indexTechnician()
    {
        $users = User::with(['role', 'room', 'supportingUnit'])
            ->where('role_id', 6)
            ->whereNotNull('approved_by')
            ->orderBy('id', 'desc')
            ->get();

        $rooms = Room::orderBy('name', 'asc')->get();
        $supportingUnits = SupportingUnit::orderBy('name', 'asc')->get();

        return Inertia::render('UserManagement/Technician', [
            'users' => $users,
            'rooms' => $rooms,
            'supportingUnits' => $supportingUnits,
        ]);
    }

    /**
     * Display a listing of room head users.
     */
    public function indexRoomHead()
    {
        $users = User::with(['role', 'room', 'supportingUnit'])
            ->where('role_id', 7)
            ->whereNotNull('approved_by')
            ->orderBy('id', 'desc')
            ->get();

        $rooms = Room::orderBy('name', 'asc')->get();
        $supportingUnits = SupportingUnit::orderBy('name', 'asc')->get();

        return Inertia::render('UserManagement/RoomHead', [
            'users' => $users,
            'rooms' => $rooms,
            'supportingUnits' => $supportingUnits,
        ]);
    }

    /**
     * Display a listing of reporter users.
     */
    public function indexReporter()
    {
        $users = User::with(['role', 'room', 'supportingUnit'])
            ->where('role_id', 8)
            ->whereNotNull('approved_by')
            ->orderBy('id', 'desc')
            ->get();

        $rooms = Room::orderBy('name', 'asc')->get();
        $supportingUnits = SupportingUnit::orderBy('name', 'asc')->get();

        return Inertia::render('UserManagement/Reporter', [
            'users' => $users,
            'rooms' => $rooms,
            'supportingUnits' => $supportingUnits,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:150',
            'nip' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('users')->whereNull('deleted_at')],
            'email' => ['required', 'string', 'email', 'max:100', \Illuminate\Validation\Rule::unique('users')->whereNull('deleted_at')],
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'room_id' => 'nullable|exists:rooms,id',
            'supporting_unit_id' => 'nullable|exists:supporting_units,id',
            'phone_number' => 'nullable|string|max:20',
        ];

        $validated = $request->validate($rules);
        $validated['password'] = Hash::make($request->password);
        $validated['uuid'] = (string) Str::uuid();
        $validated['is_active'] = true; // Admin-created user is active by default
        $validated['approved_by'] = Auth::id();
        $validated['approved_at'] = now();

        User::create($validated);

        return redirect()->back()->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:150',
            'nip' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'email' => ['required', 'string', 'email', 'max:100', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'room_id' => 'nullable|exists:rooms,id',
            'supporting_unit_id' => 'nullable|exists:supporting_units,id',
            'phone_number' => 'nullable|string|max:20',
        ];

        $validated = $request->validate($rules);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Toggle active status for user.
     */
    public function toggleActive(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return redirect()->back()->with('success', 'Status user berhasil diperbarui.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }
}
