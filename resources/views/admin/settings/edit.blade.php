@extends('admin.layout')

@section('title', 'Settings')
@section('subtitle', 'Update your own credentials for the Laravel admin portal.')

@section('content')
    <section class="admin-grid">
        <div class="admin-card">
            <h2>Profile</h2>
            <div class="admin-mini-list" style="margin-top: 1rem;">
                <div class="admin-mini-item">
                    <strong>Username</strong>
                    <p class="admin-note">{{ $admin?->username }}</p>
                </div>
                <div class="admin-mini-item">
                    <strong>Email</strong>
                    <p class="admin-note">{{ $admin?->email ?: 'No email set' }}</p>
                </div>
                <div class="admin-mini-item">
                    <strong>Role</strong>
                    <p class="admin-note">{{ ucfirst($admin?->role ?? 'staff') }}</p>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <h2>Change Password</h2>
            <form method="POST" action="{{ route('admin.settings.password') }}" class="admin-stack" style="margin-top: 1rem;">
                @csrf
                @method('PUT')

                <div class="admin-field">
                    <label for="current_password">Current Password</label>
                    <input id="current_password" type="password" name="current_password" class="admin-input" required>
                </div>

                <div class="admin-field">
                    <label for="new_password">New Password</label>
                    <input id="new_password" type="password" name="new_password" class="admin-input" required>
                </div>

                <div class="admin-field">
                    <label for="new_password_confirmation">Confirm New Password</label>
                    <input id="new_password_confirmation" type="password" name="new_password_confirmation" class="admin-input" required>
                </div>

                <button type="submit" class="admin-btn admin-btn-primary">Update Password</button>
            </form>
        </div>
    </section>
@endsection
