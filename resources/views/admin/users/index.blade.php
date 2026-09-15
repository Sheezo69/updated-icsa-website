@extends('admin.layout')

@section('title', 'Users')
@section('subtitle', 'Create admin or staff accounts and manage existing access.')

@section('content')
    <section class="admin-grid">
        <div class="admin-card">
            <h2>Create User</h2>
            <p class="admin-note">New users can immediately log in through the Laravel admin portal.</p>

            <form method="POST" action="{{ route('admin.users.store') }}" class="admin-form-grid" style="margin-top: 1rem;">
                @csrf

                <div class="admin-field">
                    <label for="username">Username</label>
                    <input id="username" name="username" class="admin-input" value="{{ old('username') }}" required>
                </div>

                <div class="admin-field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" class="admin-input" value="{{ old('email') }}">
                </div>

                <div class="admin-field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" class="admin-input" required>
                </div>

                <div class="admin-field">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="admin-select">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <fieldset class="admin-field admin-field-full admin-permission-fieldset">
                    <legend>Staff access</legend>
                    <p class="admin-note">These permissions apply only when the role is Staff.</p>
                    <div class="admin-permission-options">
                        <label class="admin-checkbox">
                            <input type="checkbox" name="can_manage_courses" value="1" @checked(old('can_manage_courses'))>
                            Manage Courses
                        </label>
                        <label class="admin-checkbox">
                            <input type="checkbox" name="can_manage_media" value="1" @checked(old('can_manage_media'))>
                            Manage Media Library
                        </label>
                    </div>
                </fieldset>

                <div class="admin-actions admin-field-full">
                    <button type="submit" class="admin-btn admin-btn-primary">Create User</button>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <h2>Account Notes</h2>
            <div class="admin-mini-list" style="margin-top: 1rem;">
                <div class="admin-mini-item">
                    <strong>Roles</strong>
                    <p class="admin-note">Admins always have full access. Staff can access Courses and Media Library only when you grant those permissions.</p>
                </div>
                <div class="admin-mini-item">
                    <strong>Password resets</strong>
                    <p class="admin-note">Resetting a password clears any login lockout state for that user.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-table-wrap" style="margin-top: 1rem;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Section Access</th>
                    <th>Assigned Inquiries</th>
                    <th>Last Login</th>
                    <th>Created</th>
                    <th>Reset Password</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->username }}</strong><br>
                            <span class="admin-muted">{{ $user->email ?: 'No email set' }}</span>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge-{{ $user->role }}">{{ $user->role }}</span>
                        </td>
                        <td>
                            @if ($user->isOwner())
                                <span class="admin-badge admin-badge-admin">Full access</span>
                            @else
                                <form method="POST" action="{{ route('admin.users.permissions', $user) }}" class="admin-permission-form">
                                    @csrf
                                    @method('PUT')
                                    <label class="admin-checkbox">
                                        <input type="checkbox" name="can_manage_courses" value="1" @checked($user->can_manage_courses)>
                                        Courses
                                    </label>
                                    <label class="admin-checkbox">
                                        <input type="checkbox" name="can_manage_media" value="1" @checked($user->can_manage_media)>
                                        Media
                                    </label>
                                    <button type="submit" class="admin-btn admin-btn-secondary">Save Access</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            @if ($user->role === \App\Models\Admin::ROLE_STAFF)
                                <a class="admin-assigned-link" href="{{ route('admin.inquiries.index', ['assignment' => $user->id]) }}">
                                    <strong>{{ $user->assigned_inquiries_count }}</strong>
                                    <span>View assigned</span>
                                </a>
                            @else
                                <span class="admin-muted">Not assignable</span>
                            @endif
                        </td>
                        <td>{{ optional($user->last_login)->format('M d, Y H:i') ?: 'Never' }}</td>
                        <td>{{ optional($user->created_at)->format('M d, Y') }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.password', $user) }}" class="admin-inline-actions">
                                @csrf
                                @method('PUT')
                                <input type="password" name="new_password" class="admin-input" placeholder="New password" required style="min-width: 180px;">
                                <button type="submit" class="admin-btn admin-btn-secondary">Reset</button>
                            </form>
                        </td>
                        <td>
                            @if (($currentAdmin ?? null)?->id !== $user->id)
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-delete-button"><span class="text">Delete</span><span class="icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M24 20.188l-8.315-8.209 8.2-8.282-3.697-3.697-8.212 8.318-8.31-8.203-3.666 3.666 8.321 8.24-8.206 8.313 3.666 3.666 8.237-8.318 8.285 8.203z"></path></svg></span></button>
                                </form>
                            @else
                                <span class="admin-muted">Current user</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
