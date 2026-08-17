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

        $storedHash = $admin->password_hash;

        // If the stored hash is bcrypt (starts with $2y$, $2a$, or $2b$) use Laravel's Hash::check.
        if (preg_match('/^\$(2y|2a|2b)\$/', $storedHash)) {
            if (! Hash::check($credentials['password'], $storedHash)) {
                $admin->recordFailedLogin();

                return back()->withInput($request->only('username'))->withErrors([
                    'username' => 'Invalid username or password.',
                ]);
            }
        } else {
            // Legacy or non-bcrypt hash (e.g., PHP's password_hash with PASSWORD_DEFAULT/argon2).
            // Use password_verify to support those hashes. On successful verification, migrate to Laravel's Hash (bcrypt).
            if (! password_verify($credentials['password'], $storedHash)) {
                $admin->recordFailedLogin();

                return back()->withInput($request->only('username'))->withErrors([
                    'username' => 'Invalid username or password.',
                ]);
            }

            // Migrate legacy hash to bcrypt for future logins
            $admin->forceFill(['password_hash' => Hash::make($credentials['password'])])->save();
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
