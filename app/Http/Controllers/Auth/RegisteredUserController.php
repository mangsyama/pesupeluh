<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
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
        return Inertia::render('Auth/Register');
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
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $profilePhotoPath = '/storage/' . $path;
        } elseif ($request->filled('profile_photo') && str_starts_with($request->profile_photo, 'data:image')) {
            $data = $request->profile_photo;
            $image_parts = explode(";base64,", $data);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1] ?? 'png';
            $image_base64 = base64_decode($image_parts[1]);
            $filename = 'profile_' . uniqid() . '.' . $image_type;
            
            \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('profile_photos');
            \Illuminate\Support\Facades\Storage::disk('public')->put('profile_photos/' . $filename, $image_base64);
            $profilePhotoPath = '/storage/profile_photos/' . $filename;
        }

        $reporterRole = \Illuminate\Support\Facades\DB::table('roles')->where('name', 'REPORTER')->first();

        $user = User::create([
            'name' => $request->name,
            'nip' => $request->nip,
            'username' => $request->username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'face_descriptor' => $request->face_descriptor,
            'role_id' => $reporterRole ? $reporterRole->id : 6,
            'is_active' => false,
            'profile_photo_path' => $profilePhotoPath,
        ]);

        $adminUsers = User::whereHas('role', fn ($query) => $query->where('name', 'ADMINISTRATOR'))->get();
        if ($adminUsers->isNotEmpty()) {
            Notification::send($adminUsers, new NewUserRegisteredNotification($user));
        }

        event(new Registered($user));

        return redirect(route('login'))->with('status', 'Pendaftaran berhasil! Akun Anda sedang menunggu verifikasi oleh Administrator.');
    }
}
