<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use App\Services\SecureFileUpload;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'supportingUnits' => \App\Models\SupportingUnit::orderBy('name', 'asc')->get(['id', 'name', 'type']),
            'rooms' => \App\Models\Room::orderBy('name', 'asc')->get(['id', 'name', 'building_name', 'location_floor']),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nip' => ['required', 'digits:18', \Illuminate\Validation\Rule::unique('users')->whereNull('deleted_at')],
            'username' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('users')->whereNull('deleted_at')],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users')->whereNull('deleted_at')],
            'phone_number' => ['required', 'regex:/^\d+$/', 'max:15'],
            'supporting_unit_id' => 'nullable|exists:supporting_units,id',
            'room_id' => 'required|exists:rooms,id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'face_descriptor' => 'nullable|array',
            'profile_photo' => ['required', function ($attribute, $value, $fail) use ($request) {
                if ($request->hasFile('profile_photo')) {
                    $file = $request->file('profile_photo');
                    if (!str_starts_with($file->getMimeType(), 'image/')) {
                        $fail('File profile photo harus berupa gambar.');
                    }
                } elseif (is_string($value)) {
                    if (!str_starts_with($value, 'data:image/')) {
                        $fail('File profile photo harus berupa gambar.');
                    }
                } else {
                    $fail('File profile photo harus berupa gambar.');
                }
            }],
        ]);

        $profilePhotoPath = null;

        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = SecureFileUpload::saveUploadedFile($request->file('profile_photo'), 'profile_photos', 'profile_');
        } elseif ($request->filled('profile_photo') && str_starts_with($request->profile_photo, 'data:image')) {
            $profilePhotoPath = SecureFileUpload::saveBase64($request->profile_photo, 'profile_photos', 'profile_');
        }

        $staffRole = \Illuminate\Support\Facades\DB::table('roles')->where('name', 'STAFF')->first();

        $user = User::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'username' => $request->username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'supporting_unit_id' => $request->supporting_unit_id,
            'room_id' => $request->room_id,
            'password' => Hash::make($request->password),
            'face_descriptor' => $request->face_descriptor,
            'role_id' => $staffRole ? $staffRole->id : \App\Models\Role::STAFF,
            'is_active' => false,
            'profile_photo_path' => $profilePhotoPath,
        ]);

        $adminUsers = User::whereHas('role', fn ($query) => $query->where('name', 'ADMINISTRATOR'))->get();
        if ($adminUsers->isNotEmpty()) {
            try {
                Notification::send($adminUsers, new NewUserRegisteredNotification($user));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Gagal mengirim notifikasi Telegram/Database: ' . $e->getMessage());
            }
        }

        event(new Registered($user));

        return redirect(route('login'))->with('status', 'Pendaftaran berhasil! Akun Anda sedang menunggu verifikasi oleh Administrator.');
    }
}
