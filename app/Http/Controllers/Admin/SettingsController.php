<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminConversation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        /** @var Admin|null $admin */
        $admin = $request->attributes->get('currentAdmin');

        $tab = in_array($request->string('tab')->toString(), ['profile', 'security', 'notifications', 'system'], true)
            ? $request->string('tab')->toString()
            : 'profile';

        return view('admin.settings.edit', [
            'admin' => $admin,
            'tab' => $tab,
            'timezones' => Admin::TIMEZONES,
            'languages' => Admin::LANGUAGES,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->attributes->get('currentAdmin');
        $data = $request->validate([
            'username' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('admins', 'username')->ignore($admin->id)],
            'email' => ['nullable', 'email:rfc', 'max:190', Rule::unique('admins', 'email')->ignore($admin->id)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        $oldUsername = $admin->username;
        $oldAvatar = $admin->avatar_path;
        $avatarPath = $oldAvatar;

        if ($request->boolean('remove_avatar')) {
            $avatarPath = null;
        }

        if ($request->hasFile('avatar')) {
            try {
                $directory = public_path('uploads/admin-avatars');
                File::ensureDirectoryExists($directory);
                $filename = $admin->id.'-'.Str::uuid().'.'.$request->file('avatar')->extension();
                $request->file('avatar')->move($directory, $filename);
                $avatarPath = 'uploads/admin-avatars/'.$filename;
            } catch (\Throwable $exception) {
                report($exception);

                return back()->withInput()->with('error', 'The profile photo could not be saved. Check the uploads folder permissions.');
            }
        }

        try {
            DB::transaction(function () use ($admin, $data, $avatarPath, $oldUsername): void {
                $admin->update([
                    'username' => $data['username'],
                    'email' => $data['email'] ?: null,
                    'avatar_path' => $avatarPath,
                ]);

                if ($oldUsername !== $data['username'] && Schema::hasTable('admin_conversations')) {
                    AdminConversation::query()->where('participant_one_id', $admin->id)->update(['participant_one_name' => $data['username']]);
                    AdminConversation::query()->where('participant_two_id', $admin->id)->update(['participant_two_name' => $data['username']]);
                }
            });
        } catch (\Throwable $exception) {
            if ($avatarPath && $avatarPath !== $oldAvatar && str_starts_with($avatarPath, 'uploads/admin-avatars/')) {
                File::delete(public_path($avatarPath));
            }
            throw $exception;
        }

        if ($oldAvatar && $oldAvatar !== $avatarPath && str_starts_with($oldAvatar, 'uploads/admin-avatars/')) {
            File::delete(public_path($oldAvatar));
        }

        return redirect()->route('admin.settings.edit', ['tab' => 'profile'])->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var Admin|null $admin */
        $admin = $request->attributes->get('currentAdmin');

        abort_unless($admin instanceof Admin, 403);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $admin->password_hash)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $admin->update([
            'password_hash' => Hash::make($data['new_password']),
        ]);

        return redirect()->route('admin.settings.edit', ['tab' => 'security'])->with('success', 'Password updated successfully.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->attributes->get('currentAdmin');
        $data = $request->validate([
            'notify_email' => ['nullable', 'boolean'],
            'notify_inquiries' => ['nullable', 'boolean'],
            'notify_messages' => ['nullable', 'boolean'],
        ]);

        $admin->update([
            'notify_email' => (bool) ($data['notify_email'] ?? false),
            'notify_inquiries' => (bool) ($data['notify_inquiries'] ?? false),
            'notify_messages' => (bool) ($data['notify_messages'] ?? false),
        ]);

        return redirect()->route('admin.settings.edit', ['tab' => 'notifications'])->with('success', 'Notification preferences saved.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = $request->attributes->get('currentAdmin');
        $data = $request->validate([
            'timezone' => ['required', Rule::in(array_keys(Admin::TIMEZONES))],
            'language' => ['required', Rule::in(array_keys(Admin::LANGUAGES))],
        ]);

        $admin->update($data);

        return redirect()->route('admin.settings.edit', ['tab' => 'system'])->with('success', 'System preferences saved.');
    }
}
