<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\SecureFileUpload;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load(['role', 'room', 'supportingUnit']);

        return Inertia::render('UserProfile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $user,
            'supportingUnits' => \App\Models\SupportingUnit::orderBy('name', 'asc')->get(),
            'rooms' => \App\Models\Room::orderBy('name', 'asc')->get(),
        ]);
    }

    /**
     * Update the user's notification preferences.
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'system_notify_enabled' => 'required|boolean',
            'wa_notify_enabled' => 'required|boolean',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->update($validated);

        return back()->with('success', 'Pengaturan notifikasi berhasil diperbarui.');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->except(['profile_photo']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->profile_photo === 'delete') {
            if ($user->profile_photo_path) {
                $oldPath = str_replace('/storage/', '', $user->profile_photo_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $user->profile_photo_path = null;
        } elseif ($request->hasFile('profile_photo')) {
            // Store new photo safely
            $newPath = SecureFileUpload::saveUploadedFile($request->file('profile_photo'), 'profile_photos', 'profile_');
            if ($newPath) {
                if ($user->profile_photo_path) {
                    $oldPath = str_replace('/storage/', '', $user->profile_photo_path);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
                $user->profile_photo_path = $newPath;
            }
        } elseif ($request->filled('profile_photo') && str_starts_with($request->profile_photo, 'data:image')) {
            // Store new base64 photo safely
            $newPath = SecureFileUpload::saveBase64($request->profile_photo, 'profile_photos', 'profile_');
            if ($newPath) {
                if ($user->profile_photo_path) {
                    $oldPath = str_replace('/storage/', '', $user->profile_photo_path);
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
                }
                $user->profile_photo_path = $newPath;
            }
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's face descriptor.
     */
    public function updateFace(Request $request): RedirectResponse
    {
        $request->validate([
            'face_descriptor' => 'required|array|min:128|max:128',
        ]);

        $user = $request->user();
        $user->face_descriptor = $request->input('face_descriptor');
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'face-updated');
    }

    /**
     * Delete the user's face descriptor.
     */
    public function deleteFace(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->face_descriptor = null;
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'face-deleted');
    }
}
