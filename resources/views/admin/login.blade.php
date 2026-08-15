<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ICSA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="admin-login-shell">
        <div class="admin-login-card">
            <a href="{{ route('site.home') }}" class="admin-brand">
                <img src="{{ asset('images/ICSA-LOGO.png') }}" alt="ICSA logo">
                <span class="admin-brand-copy">
                    <strong>ICSA Portal</strong>
                    <span>Laravel admin access</span>
                </span>
            </a>

            <h1 class="admin-login-title">Sign in</h1>
            <p class="admin-login-subtitle">Use your existing admin credentials from the old site.</p>

            @include('admin.partials.flash')

            <form method="POST" action="{{ route('admin.login.submit') }}" class="admin-stack">
                @csrf

                <div class="admin-field">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        name="username"
                        class="admin-input"
                        value="{{ old('username') }}"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="admin-field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="admin-input"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-lock"></i> Sign In
                </button>
            </form>
        </div>
    </div>
</body>
</html>
