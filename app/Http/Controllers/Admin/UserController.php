<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ContactMessage;
use App\Support\AdminActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => Admin::query()->withCount('assignedInquiries')->orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request, AdminActivityLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('admins', 'username')],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,staff'],
            'can_manage_courses' => ['nullable', 'boolean'],
            'can_manage_media' => ['nullable', 'boolean'],
        ]);

        $user = Admin::query()->create([
            'username' => $data['username'],
            'email' => $data['email'] ?: null,
            'password_hash' => Hash::make($data['password']),
            'role' => $data['role'],
            'can_manage_courses' => $data['role'] === Admin::ROLE_STAFF && (bool) ($data['can_manage_courses'] ?? false),
            'can_manage_media' => $data['role'] === Admin::ROLE_STAFF && (bool) ($data['can_manage_media'] ?? false),
            'login_attempts' => 0,
        ]);
        $audit->record($request, 'user.created', 'users', 'Created a new admin panel account.', Admin::class, $user->id, $user->username, null, $this->userSnapshot($user));

        return back()->with('success', 'User created successfully.');
    }

    public function destroy(Request $request, Admin $user, AdminActivityLogger $audit): RedirectResponse
    {
        if ((int) $request->session()->get('admin_id') === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $before = $this->userSnapshot($user);
        $label = $user->username;
        $id = $user->id;
        $unassigned = ContactMessage::query()->where('assigned_to', $user->id)->update(['assigned_to' => null]);
        if ($user->avatar_path && str_starts_with($user->avatar_path, 'uploads/admin-avatars/')) {
            File::delete(public_path($user->avatar_path));
        }
        $user->delete();
        $before['inquiries_unassigned'] = $unassigned;
        $audit->record($request, 'user.deleted', 'users', 'Deleted an admin panel account.', Admin::class, $id, $label, $before);

        return back()->with('success', 'User deleted successfully.');
    }

    public function resetPassword(Request $request, Admin $user, AdminActivityLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        $user->update([
            'password_hash' => Hash::make($data['new_password']),
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
        $audit->record($request, 'user.password_reset', 'users', 'Reset an account password and cleared its lockout state.', Admin::class, $user->id, $user->username);

        return back()->with('success', 'Password reset successfully.');
    }

    public function updatePermissions(Request $request, Admin $user, AdminActivityLogger $audit): RedirectResponse
    {
        if ($user->isOwner()) {
            return back()->with('error', 'Administrator accounts always have full access.');
        }

        $data = $request->validate([
            'can_manage_courses' => ['nullable', 'boolean'],
            'can_manage_media' => ['nullable', 'boolean'],
        ]);

        $before = $this->userSnapshot($user);
        $user->update([
            'can_manage_courses' => (bool) ($data['can_manage_courses'] ?? false),
            'can_manage_media' => (bool) ($data['can_manage_media'] ?? false),
        ]);
        $audit->record($request, 'user.permissions_updated', 'users', 'Updated staff section access.', Admin::class, $user->id, $user->username, $before, $this->userSnapshot($user));

        return back()->with('success', 'Staff permissions updated.');
    }

    private function userSnapshot(Admin $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'can_manage_courses' => (bool) $user->can_manage_courses,
            'can_manage_media' => (bool) $user->can_manage_media,
        ];
    }
}
