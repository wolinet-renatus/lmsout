<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\StrongPassword;
use App\Services\FileUploadService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Profile Controller
 *
 * This controller handles the management of the admin profile.
 *
 * @package App\Http\Controllers\Admin
 * @author Renatus Bernardo
 */
class ProfileController extends Controller
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private FileUploadService $fileUploadService) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $photoDimensions = null;

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            $fullPath = Storage::disk('public')->path($user->profile_photo);
            $size = @getimagesize($fullPath);
            if (is_array($size)) {
                $photoDimensions = $size[0] . ' × ' . $size[1] . ' px';
            }
        }

        return view('admin.profile.edit', [
            'user' => $user,
            'photoPresets' => FileUploadService::profilePhotoPresets(),
            'photoDimensions' => $photoDimensions,
        ]);
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:8192'],
            'resize_preset' => ['nullable', Rule::in(array_keys(FileUploadService::profilePhotoPresets()))],
            'resize_mode' => ['nullable', Rule::in(['crop', 'fit'])],
            'resize_width' => ['nullable', 'integer', 'min:64', 'max:2048'],
            'resize_height' => ['nullable', 'integer', 'min:64', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', new StrongPassword()],
        ]);

        if ($request->boolean('remove_photo') && ! $request->hasFile('profile_photo')) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->profile_photo = null;
        }

        if ($request->hasFile('profile_photo')) {
            $preset = $validated['resize_preset'] ?? 'square_400';
            $resizeOptions = [
                'preset' => $preset,
            ];

            if ($preset === 'custom') {
                $resizeOptions['width'] = $validated['resize_width'] ?? 400;
                $resizeOptions['height'] = $validated['resize_height'] ?? 400;
                $resizeOptions['mode'] = $validated['resize_mode'] ?? 'crop';
            }

            $newPhotoPath = $this->fileUploadService->uploadProfilePhoto(
                $request->file('profile_photo'),
                'admins/photos',
                $resizeOptions
            );

            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->profile_photo = $newPhotoPath;
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->password_changed_at = now();
            $user->force_password_reset = false;
        }

        $user->save();

        ToastMagic::success('Profile updated successfully.');

        return redirect()->route('admin.profile.edit');
    }
}
