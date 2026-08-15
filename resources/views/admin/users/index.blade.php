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
                    <p class="admin-note">Admins can manage users. Staff can handle inquiries, settings, and courses.</p>
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
                                    <button type="submit" class="admin-btn admin-btn-danger">Delete</button>
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
