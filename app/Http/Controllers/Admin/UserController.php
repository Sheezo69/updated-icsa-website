<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => Admin::query()->orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('admins', 'username')],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,staff'],
        ]);

        Admin::query()->create([
            'username' => $data['username'],
            'email' => $data['email'] ?: null,
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'],
            'login_attempts' => 0,
        ]);

        return back()->with('success', 'User created successfully.');
    }

    public function destroy(Request $request, Admin $user): RedirectResponse
    {
        if ((int) $request->session()->get('admin_id') === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    public function resetPassword(Request $request, Admin $user): RedirectResponse
    {
        $data = $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        $user->update([
            'password_hash' => Hash::make($data['new_password']),
            'login_attempts' => 0,
            'locked_until' => null,
        ]);

        return back()->with('success', 'Password reset successfully.');
    }
}
