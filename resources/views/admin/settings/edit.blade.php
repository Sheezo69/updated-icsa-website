@extends('admin.layout')

@section('title', 'Settings')
@section('subtitle', 'Manage your account, security, notifications and system preferences.')
@section('title_icon', 'fas fa-gear')

@section('content')
    @php
        $tabs = [
            'profile' => ['icon' => 'fas fa-user', 'label' => 'Profile'],
            'security' => ['icon' => 'fas fa-lock', 'label' => 'Security'],
            'notifications' => ['icon' => 'far fa-bell', 'label' => 'Notifications'],
            'system' => ['icon' => 'fas fa-sliders', 'label' => 'System Preferences'],
        ];
        $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($admin?->username ?? 'A', 0, 1));
    @endphp

    <div class="settings-shell">
        <nav class="settings-tabs" aria-label="Settings sections">
            @foreach ($tabs as $value => $item)
                <a href="{{ route('admin.settings.edit', ['tab' => $value]) }}" class="{{ $tab === $value ? 'is-active' : '' }}" @if ($tab === $value) aria-current="page" @endif>
                    <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($tab === 'profile')
            <section class="settings-panel settings-profile-panel">
                <header class="settings-panel-header">
                    <span class="settings-section-icon"><i class="fas fa-user-pen" aria-hidden="true"></i></span>
                    <div><h2>Profile</h2><p>Update your personal information and profile details.</p></div>
                    <span class="settings-status-pill"><i></i> Active</span>
                </header>

                <form method="POST" action="{{ route('admin.settings.profile', ['tab' => 'profile']) }}" enctype="multipart/form-data" class="settings-profile-form">
                    @csrf
                    @method('PUT')

                    <div class="settings-avatar-column">
                        <label class="settings-avatar" for="avatar" data-avatar-preview>
                            @if ($admin?->avatar_path)
                                <img src="{{ asset($admin->avatar_path) }}" alt="{{ $admin->username }} profile photo">
                            @else
                                <span>{{ $initial }}</span>
                            @endif
                            <i class="fas fa-camera" aria-hidden="true"></i>
                        </label>
                        <label for="avatar" class="admin-btn admin-btn-secondary settings-photo-button">Change Photo</label>
                        <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" hidden data-avatar-input>
                        <small>JPG, PNG or WEBP · Max 2MB</small>
                        @if ($admin?->avatar_path)
                            <label class="settings-remove-photo"><input type="checkbox" name="remove_avatar" value="1"> Remove current photo</label>
                        @endif
                    </div>

                    <div class="settings-form-fields">
                        <label class="settings-field">
                            <span>Username <b>*</b></span>
                            <span class="settings-input-wrap"><i class="far fa-user"></i><input name="username" value="{{ old('username', $admin?->username) }}" maxlength="30" required></span>
                        </label>
                        <label class="settings-field">
                            <span>Email Address</span>
                            <span class="settings-input-wrap"><i class="far fa-envelope"></i><input type="email" name="email" value="{{ old('email', $admin?->email) }}" placeholder="name@example.com"></span>
                        </label>
                        <label class="settings-field">
                            <span>Role</span>
                            <span class="settings-input-wrap is-readonly"><i class="fas fa-shield-halved"></i><input value="{{ ucfirst($admin?->role ?? 'staff') }}" readonly></span>
                        </label>
                        <div class="settings-form-actions">
                            <button type="submit" class="admin-btn admin-btn-primary"><i class="far fa-floppy-disk"></i> Save Changes</button>
                        </div>
                    </div>
                </form>
            </section>
        @elseif ($tab === 'security')
            <section class="settings-panel settings-security-panel">
                <header class="settings-panel-header">
                    <span class="settings-section-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
                    <div><h2>Change Password</h2><p>Keep your account secure with a strong, unique password.</p></div>
                </header>

                <form method="POST" action="{{ route('admin.settings.password', ['tab' => 'security']) }}" class="settings-security-form" data-password-form>
                    @csrf
                    @method('PUT')
                    <label class="settings-field"><span>Current Password <b>*</b></span><span class="settings-input-wrap"><i class="fas fa-lock"></i><input type="password" name="current_password" autocomplete="current-password" placeholder="Enter current password" required><button type="button" data-toggle-password aria-label="Show password"><i class="far fa-eye"></i></button></span></label>
                    <label class="settings-field"><span>New Password <b>*</b></span><span class="settings-input-wrap"><i class="fas fa-key"></i><input type="password" name="new_password" autocomplete="new-password" placeholder="Enter new password" minlength="8" required data-new-password><button type="button" data-toggle-password aria-label="Show password"><i class="far fa-eye"></i></button></span></label>
                    <label class="settings-field"><span>Confirm New Password <b>*</b></span><span class="settings-input-wrap"><i class="fas fa-check-double"></i><input type="password" name="new_password_confirmation" autocomplete="new-password" placeholder="Confirm new password" minlength="8" required><button type="button" data-toggle-password aria-label="Show password"><i class="far fa-eye"></i></button></span></label>

                    <div class="settings-strength">
                        <div><span>Password Strength</span><strong data-strength-label>Waiting</strong></div>
                        <span class="settings-strength-track"><i data-strength-bar></i></span>
                    </div>
                    <div class="settings-requirements">
                        <strong>Password requirements</strong>
                        <div>
                            <span data-requirement="length"><i class="far fa-circle-check"></i> At least 8 characters</span>
                            <span data-requirement="uppercase"><i class="far fa-circle-check"></i> One uppercase letter</span>
                            <span data-requirement="number"><i class="far fa-circle-check"></i> One number</span>
                            <span data-requirement="special"><i class="far fa-circle-check"></i> One special character</span>
                        </div>
                    </div>
                    <div class="settings-form-actions"><button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-lock"></i> Update Password</button></div>
                </form>
            </section>
        @elseif ($tab === 'notifications')
            <section class="settings-panel settings-notification-panel">
                <header class="settings-panel-header">
                    <span class="settings-section-icon"><i class="far fa-bell" aria-hidden="true"></i></span>
                    <div><h2>Notification Preferences</h2><p>Choose which account alerts you want to receive.</p></div>
                </header>

                <form method="POST" action="{{ route('admin.settings.notifications', ['tab' => 'notifications']) }}">
                    @csrf
                    @method('PUT')
                    <div class="settings-notification-list">
                        <label class="settings-notification-row">
                            <span class="settings-notification-icon"><i class="far fa-envelope"></i></span>
                            <span><strong>Email Notifications</strong><small>Allow notification emails to your profile email address.</small></span>
                            <input type="checkbox" name="notify_email" value="1" @checked(old('notify_email', $admin?->notify_email ?? true))><i class="settings-switch"></i><em>Enabled</em>
                        </label>
                        <label class="settings-notification-row">
                            <span class="settings-notification-icon"><i class="far fa-message"></i></span>
                            <span><strong>New Inquiry Alerts</strong><small>Receive a copy when a new website inquiry is submitted.</small></span>
                            <input type="checkbox" name="notify_inquiries" value="1" @checked(old('notify_inquiries', $admin?->notify_inquiries ?? true))><i class="settings-switch"></i><em>Enabled</em>
                        </label>
                        <label class="settings-notification-row">
                            <span class="settings-notification-icon"><i class="far fa-comments"></i></span>
                            <span><strong>Message Notifications</strong><small>Show unread badges for new internal team messages.</small></span>
                            <input type="checkbox" name="notify_messages" value="1" @checked(old('notify_messages', $admin?->notify_messages ?? true))><i class="settings-switch"></i><em>Enabled</em>
                        </label>
                    </div>
                    <div class="settings-form-actions"><button type="submit" class="admin-btn admin-btn-primary"><i class="far fa-floppy-disk"></i> Save Preferences</button></div>
                </form>
            </section>
        @else
            <section class="settings-panel settings-system-panel">
                <header class="settings-panel-header">
                    <span class="settings-section-icon"><i class="fas fa-gear" aria-hidden="true"></i></span>
                    <div><h2>System Preferences</h2><p>Configure your regional and interface settings.</p></div>
                </header>
                <form method="POST" action="{{ route('admin.settings.preferences', ['tab' => 'system']) }}" class="settings-system-form">
                    @csrf
                    @method('PUT')
                    <label class="settings-field"><span>Timezone</span><span class="settings-input-wrap"><i class="fas fa-globe"></i><select name="timezone" required>@foreach ($timezones as $value => $label)<option value="{{ $value }}" @selected(old('timezone', $admin?->timezone ?? 'Asia/Kuwait') === $value)>{{ $label }}</option>@endforeach</select></span></label>
                    <label class="settings-field"><span>Language</span><span class="settings-input-wrap"><i class="fas fa-language"></i><select name="language" required>@foreach ($languages as $value => $label)<option value="{{ $value }}" @selected(old('language', $admin?->language ?? 'en') === $value)>{{ $label }}</option>@endforeach</select></span></label>
                    <div class="settings-info-banner"><i class="fas fa-circle-info"></i><span>Timezone changes apply to dates and times across your admin panel. English is currently the supported interface language.</span></div>
                    <div class="settings-form-actions"><button type="submit" class="admin-btn admin-btn-primary"><i class="far fa-floppy-disk"></i> Save Preferences</button></div>
                </form>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const avatarInput = document.querySelector('[data-avatar-input]');
    const avatarPreview = document.querySelector('[data-avatar-preview]');
    avatarInput?.addEventListener('change', () => {
        const file = avatarInput.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;
        const image = avatarPreview.querySelector('img') || document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = 'Selected profile photo';
        avatarPreview.querySelector('span')?.remove();
        avatarPreview.prepend(image);
    });
    document.querySelectorAll('[data-toggle-password]').forEach(button => button.addEventListener('click', () => {
        const input = button.parentElement.querySelector('input');
        input.type = input.type === 'password' ? 'text' : 'password';
        button.querySelector('i').className = input.type === 'password' ? 'far fa-eye' : 'far fa-eye-slash';
    }));
    const password = document.querySelector('[data-new-password]');
    const strengthBar = document.querySelector('[data-strength-bar]');
    const strengthLabel = document.querySelector('[data-strength-label]');
    const tests = { length: value => value.length >= 8, uppercase: value => /[A-Z]/.test(value), number: value => /\d/.test(value), special: value => /[^A-Za-z0-9]/.test(value) };
    const updateStrength = () => {
        if (!password) return;
        const score = Object.entries(tests).filter(([name, test]) => {
            const passed = test(password.value);
            document.querySelector(`[data-requirement="${name}"]`)?.classList.toggle('is-met', passed);
            return passed;
        }).length;
        const labels = ['Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
        strengthBar.style.width = `${score * 25}%`;
        strengthBar.dataset.score = score;
        strengthLabel.textContent = password.value ? labels[score] : 'Waiting';
        strengthLabel.dataset.score = score;
    };
    password?.addEventListener('input', updateStrength);
    updateStrength();
    document.querySelectorAll('.settings-notification-row input').forEach(input => {
        const sync = () => input.closest('label').querySelector('em').textContent = input.checked ? 'Enabled' : 'Disabled';
        input.addEventListener('change', sync);
        sync();
    });
})();
</script>
@endpush
