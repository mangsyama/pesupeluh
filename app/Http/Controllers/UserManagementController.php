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
    private function markRegistrationNotificationAsRead(User $user): void
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if ($currentUser instanceof User) {
            $currentUser->unreadNotifications()
                ->where(function ($query) use ($user) {
                    $query->where('data->user_id', $user->id)
                          ->orWhere('data->route', 'like', "%{$user->uuid}%")
                          ->orWhere('data->route', 'like', "%{$user->id}%");
                })
                ->get()
                ->each(fn ($notification) => $notification->markAsRead());
        }
    }

    /**
     * Display a listing of user registration approval requests.
     */
    public function indexApprovals()
    {
        /** @var \App\Models\User|null $currentUser */
        $currentUser = Auth::user();
        if ($currentUser instanceof User) {
            // Auto mark unread registration notifications for users that are already approved
            $processedUserIds = User::whereNotNull('approved_by')->pluck('id');
            if ($processedUserIds->isNotEmpty()) {
                $currentUser->unreadNotifications()
                    ->where('data->type', 'user')
                    ->whereIn('data->user_id', $processedUserIds)
                    ->get()
                    ->each(fn ($notification) => $notification->markAsRead());
            }
        }

        return Inertia::render('UserManagement/Approval/Index', [
            'users' => Inertia::defer(fn() => User::with(['role', 'room', 'supportingUnit'])
                ->whereNull('approved_by')
                ->orderBy('id', 'desc')
                ->get()),
        ]);
    }

    /**
     * Display single user registration approval detail page.
     */
    public function showApprovalDetail(User $user)
    {
        // Ensure user is pending approval
        if ($user->approved_by !== null || $user->is_active) {
            return redirect()->route('users.approvals')->with('info', 'Pendaftaran user ini sudah diproses.');
        }

        // Automatically mark unread registration notifications for this user as read for current admin
        $this->markRegistrationNotificationAsRead($user);

        return Inertia::render('UserManagement/Approval/Detail', [
            'targetUser' => $user->load(['role', 'room', 'supportingUnit']),
            'roles' => Inertia::defer(fn() => Role::orderBy('id', 'asc')->get()),
            'rooms' => Inertia::defer(fn() => Room::orderBy('name', 'asc')->get()),
            'supportingUnits' => Inertia::defer(fn() => SupportingUnit::orderBy('name', 'asc')->get()),
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

        $this->markRegistrationNotificationAsRead($user);

        return redirect()->route('users.approvals')->with('success', 'Pendaftaran user berhasil disetujui.');
    }

    /**
     * Display a listing of all approved users by role.
     */
    public function index()
    {
        // All available permission keys for the permission checkbox UI
        $allPermissionKeys = [
            [
                'group' => 'Menu Utama',
                'permissions' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard'],
                ],
            ],
            [
                'group' => 'Layanan & Laporan',
                'permissions' => [
                    ['key' => 'services.index', 'label' => 'Layanan Penunjang'],
                    ['key' => 'reports.history', 'label' => 'Riwayat Pelaporan'],
                    ['key' => 'reports-management.index', 'label' => 'Manajemen Laporan'],
                    ['key' => 'reports.index', 'label' => 'Ekspor Laporan'],
                ],
            ],
            [
                'group' => 'Teknisi & Operasional',
                'permissions' => [
                    ['key' => 'technicians.position', 'label' => 'Posisi & Status Teknisi'],
                    ['key' => 'service-management.working-hours', 'label' => 'Jam Operasional Unit'],
                ],
            ],
            [
                'group' => 'Master Data',
                'permissions' => [
                    ['key' => 'service-management.rooms', 'label' => 'Manajemen Ruangan'],
                    ['key' => 'service-management.categories', 'label' => 'Kategori Kerusakan'],
                    ['key' => 'service-management.supporting-units', 'label' => 'Unit Penunjang'],
                    ['key' => 'users.approvals', 'label' => 'Persetujuan Pengguna'],
                    ['key' => 'users.index', 'label' => 'Daftar Pengguna'],
                ],
            ],
            [
                'group' => 'Sistem',
                'permissions' => [
                    ['key' => 'admin.wa-gateway.index', 'label' => 'WhatsApp Gateway'],
                    ['key' => 'admin.qr-code.index', 'label' => 'Generator QR Code'],
                    ['key' => 'settings.index', 'label' => 'Pengaturan'],
                    ['key' => 'design-system.index', 'label' => 'Design System'],
                ],
            ],
        ];

        return Inertia::render('UserManagement/Index', [
            'users' => Inertia::defer(fn() => User::with(['role', 'room', 'supportingUnit'])
                ->whereNotNull('approved_by')
                ->orderBy('id', 'desc')
                ->get()),
            'roles' => Inertia::defer(fn() => Role::orderBy('id', 'asc')->get()),
            'rooms' => Inertia::defer(fn() => Room::orderBy('name', 'asc')->get()),
            'supportingUnits' => Inertia::defer(fn() => SupportingUnit::orderBy('name', 'asc')->get()),
            'allPermissionKeys' => $allPermissionKeys,
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
            'username' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::unique('users')->whereNull('deleted_at')],
            'email' => ['required', 'string', 'email', 'max:100', \Illuminate\Validation\Rule::unique('users')->whereNull('deleted_at')],
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'room_id' => 'nullable|exists:rooms,id',
            'supporting_unit_id' => 'nullable|exists:supporting_units,id',
            'phone_number' => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:50',
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
     * Display the specified user detail page (read-only).
     */
    public function show(User $user)
    {
        $allPermissionKeys = [
            [
                'group' => 'Menu Utama',
                'permissions' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard'],
                ],
            ],
            [
                'group' => 'Layanan & Laporan',
                'permissions' => [
                    ['key' => 'services.index', 'label' => 'Layanan Penunjang'],
                    ['key' => 'reports.history', 'label' => 'Riwayat Pelaporan'],
                    ['key' => 'reports-management.index', 'label' => 'Manajemen Laporan'],
                    ['key' => 'reports.index', 'label' => 'Ekspor Laporan'],
                ],
            ],
            [
                'group' => 'Teknisi & Operasional',
                'permissions' => [
                    ['key' => 'technicians.position', 'label' => 'Posisi & Status Teknisi'],
                    ['key' => 'service-management.working-hours', 'label' => 'Jam Operasional Unit'],
                ],
            ],
            [
                'group' => 'Master Data',
                'permissions' => [
                    ['key' => 'service-management.rooms', 'label' => 'Manajemen Ruangan'],
                    ['key' => 'service-management.categories', 'label' => 'Kategori Kerusakan'],
                    ['key' => 'service-management.supporting-units', 'label' => 'Unit Penunjang'],
                    ['key' => 'users.approvals', 'label' => 'Persetujuan Pengguna'],
                    ['key' => 'users.index', 'label' => 'Daftar Pengguna'],
                ],
            ],
            [
                'group' => 'Sistem',
                'permissions' => [
                    ['key' => 'admin.wa-gateway.index', 'label' => 'WhatsApp Gateway'],
                    ['key' => 'admin.qr-code.index', 'label' => 'Generator QR Code'],
                    ['key' => 'settings.index', 'label' => 'Pengaturan'],
                    ['key' => 'design-system.index', 'label' => 'Design System'],
                ],
            ],
        ];

        return Inertia::render('UserManagement/Show', [
            'targetUser' => $user->load(['role', 'room', 'supportingUnit']),
            'roles' => Role::orderBy('id', 'asc')->get(),
            'allPermissionKeys' => $allPermissionKeys,
        ]);
    }

    /**
     * Display edit page for a specific user.
     */
    public function edit(User $user)
    {
        $allPermissionKeys = [
            [
                'group' => 'Menu Utama',
                'permissions' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard'],
                ],
            ],
            [
                'group' => 'Layanan & Laporan',
                'permissions' => [
                    ['key' => 'services.index', 'label' => 'Layanan Penunjang'],
                    ['key' => 'reports.history', 'label' => 'Riwayat Pelaporan'],
                    ['key' => 'reports-management.index', 'label' => 'Manajemen Laporan'],
                    ['key' => 'reports.index', 'label' => 'Ekspor Laporan'],
                ],
            ],
            [
                'group' => 'Teknisi & Operasional',
                'permissions' => [
                    ['key' => 'technicians.position', 'label' => 'Posisi & Status Teknisi'],
                    ['key' => 'service-management.working-hours', 'label' => 'Jam Operasional Unit'],
                ],
            ],
            [
                'group' => 'Master Data',
                'permissions' => [
                    ['key' => 'service-management.rooms', 'label' => 'Manajemen Ruangan'],
                    ['key' => 'service-management.categories', 'label' => 'Kategori Kerusakan'],
                    ['key' => 'service-management.supporting-units', 'label' => 'Unit Penunjang'],
                    ['key' => 'users.approvals', 'label' => 'Persetujuan Pengguna'],
                    ['key' => 'users.index', 'label' => 'Daftar Pengguna'],
                ],
            ],
            [
                'group' => 'Sistem',
                'permissions' => [
                    ['key' => 'admin.wa-gateway.index', 'label' => 'WhatsApp Gateway'],
                    ['key' => 'admin.qr-code.index', 'label' => 'Generator QR Code'],
                    ['key' => 'settings.index', 'label' => 'Pengaturan'],
                    ['key' => 'design-system.index', 'label' => 'Design System'],
                ],
            ],
        ];

        return Inertia::render('UserManagement/Edit', [
            'targetUser' => $user->load(['role', 'room', 'supportingUnit']),
            'roles' => Inertia::defer(fn() => Role::orderBy('id', 'asc')->get()),
            'rooms' => Inertia::defer(fn() => Room::orderBy('name', 'asc')->get()),
            'supportingUnits' => Inertia::defer(fn() => SupportingUnit::orderBy('name', 'asc')->get()),
            'allPermissionKeys' => $allPermissionKeys,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:150',
            'nip' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'username' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'email' => ['required', 'string', 'email', 'max:100', \Illuminate\Validation\Rule::unique('users')->ignore($user->id)->whereNull('deleted_at')],
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
            'room_id' => 'nullable|exists:rooms,id',
            'supporting_unit_id' => 'nullable|exists:supporting_units,id',
            'phone_number' => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'page_permissions' => 'nullable|array',
            'page_permissions.*' => 'string',
            'use_role_default' => 'nullable|boolean',
        ];

        $validated = $request->validate($rules);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($request->password);
        } else {
            unset($validated['password']);
        }

        if ($request->has('use_role_default') && $request->boolean('use_role_default')) {
            $validated['page_permissions'] = null;
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Data profil & akun pengguna berhasil diperbarui.');
    }

    /**
     * Toggle active status for user.
     */
    public function toggleActive(User $user)
    {
        if ((int) $user->id === (int) Auth::id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

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
        if ((int) $user->id === (int) Auth::id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $this->markRegistrationNotificationAsRead($user);

        $user->delete();

        $previousUrl = url()->previous();
        if (Str::contains($previousUrl, '/users/approvals')) {
            return redirect()->route('users.approvals')->with('success', 'Pendaftaran user berhasil ditolak.');
        }

        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }

    /**
     * Update page permissions for a specific user.
     */
    public function updatePermissions(Request $request, User $user)
    {
        $validated = $request->validate([
            'page_permissions' => 'present|array',
            'page_permissions.*' => 'string',
            'use_role_default' => 'boolean',
        ]);

        if ($request->boolean('use_role_default')) {
            // Reset to role default by clearing user override
            $user->update(['page_permissions' => null]);
        } else {
            $user->update(['page_permissions' => $validated['page_permissions']]);
        }

        return redirect()->back()->with('success', 'Hak akses pengguna berhasil diperbarui.');
    }
}
