<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        /** @var Admin|null $admin */
        $admin = $request->attributes->get('currentAdmin');

        return view('admin.settings.edit', [
            'admin' => $admin,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var Admin|null $admin */
        $admin = $request->attributes->get('currentAdmin');

        abort_unless($admin instanceof Admin, 403);

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $storedHash = $admin->password_hash;

        $currentPasswordValid = false;

        if (preg_match('/^\$(2y|2a|2b)\$/', $storedHash)) {
            $currentPasswordValid = Hash::check($data['current_password'], $storedHash);
        } else {
            // Legacy or non-bcrypt hash — verify with PHP's password_verify
            $currentPasswordValid = password_verify($data['current_password'], $storedHash);
        }

        if (! $currentPasswordValid) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $admin->update([
            'password_hash' => Hash::make($data['new_password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
