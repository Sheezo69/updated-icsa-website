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

        if (! Hash::check($data['current_password'], $admin->password_hash)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $admin->update([
            'password_hash' => Hash::make($data['new_password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
