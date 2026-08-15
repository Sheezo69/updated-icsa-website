<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::query()->where('username', $credentials['username'])->first();

        if (! $admin) {
            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Invalid username or password.',
            ]);
        }

        if ($admin->isLocked()) {
            $minutes = max(1, now()->diffInMinutes($admin->locked_until));

            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Account locked. Try again in '.$minutes.' minute(s).',
            ]);
        }

        if (! Hash::check($credentials['password'], $admin->password_hash)) {
            $admin->recordFailedLogin();

            return back()->withInput($request->only('username'))->withErrors([
                'username' => 'Invalid username or password.',
            ]);
        }

        $admin->clearLoginFailures();
        $admin->forceFill(['last_login' => now()])->save();

        $request->session()->regenerate();
        $request->session()->put([
            'admin_id' => $admin->id,
            'admin_role' => $admin->role,
        ]);

        $intended = $request->session()->pull('admin_intended', route('admin.dashboard'));

        return redirect()->to($intended);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
