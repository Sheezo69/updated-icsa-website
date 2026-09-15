<!DOCTYPE html>
<html lang="{{ $currentAdmin->language ?? 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | ICSA Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    @stack('styles')
</head>
<body>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                <img src="{{ asset('images/ICSA-LOGO.png') }}" alt="ICSA logo">
                <span class="admin-brand-copy">
                    <strong>ICSA Admin</strong>
                    <span>Laravel management panel</span>
                </span>
            </a>

            <nav class="admin-sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="{{ route('admin.inquiries.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.inquiries.*') ? 'is-active' : '' }}">
                    <i class="fas fa-envelope"></i> Inquiries
                    @if (request()->routeIs('admin.inquiries.*') && isset($stats['total']))
                        <span class="admin-sidebar-count">{{ $stats['total'] }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.messages.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.messages.*') ? 'is-active' : '' }}">
                    <i class="fas fa-comments"></i> Messages
                    <span class="admin-sidebar-count admin-message-count" data-message-unread @if (($adminUnreadMessages ?? 0) < 1) hidden @endif>{{ $adminUnreadMessages ?? 0 }}</span>
                </a>
                @if (($currentAdmin ?? null)?->canAccess('courses'))
                    <a href="{{ route('admin.courses.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.courses.*') ? 'is-active' : '' }}">
                        <i class="fas fa-graduation-cap"></i> Courses
                    </a>
                @endif
                @if (($currentAdmin ?? null)?->canAccess('media'))
                    <a href="{{ route('admin.media.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.media.*') ? 'is-active' : '' }}">
                        <i class="fas fa-images"></i> Media Library
                    </a>
                @endif
                @if (($currentAdmin ?? null)?->isOwner())
                    <a href="{{ route('admin.users.index') }}" class="admin-sidebar-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
                        <i class="fas fa-users"></i> Users
                    </a>
                @endif
                <a href="{{ route('admin.settings.edit') }}" class="admin-sidebar-link {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </nav>

            <div class="admin-sidebar-foot">
                <a href="{{ route('site.home') }}" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-arrow-up-right-from-square"></i> View Website
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn-danger" style="width: 100%;">
                        <i class="fas fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="admin-main">
            <div class="admin-topbar">
                <div class="admin-page-heading">
                    @hasSection('title_icon')
                        <span class="admin-page-heading-icon"><i class="@yield('title_icon')" aria-hidden="true"></i></span>
                    @endif
                    <div>
                        <h1>@yield('title')</h1>
                        <p>@yield('subtitle')</p>
                    </div>
                </div>

                @if (($currentAdmin ?? null))
                    <div class="admin-user-pill">
                        @if ($currentAdmin->avatar_path)
                            <img src="{{ asset($currentAdmin->avatar_path) }}" alt="" class="admin-user-pill-avatar">
                        @else
                            <span class="admin-user-pill-avatar admin-user-pill-initial">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($currentAdmin->username, 0, 1)) }}</span>
                        @endif
                        <span class="admin-user-pill-copy">
                        <strong>{{ $currentAdmin->username }}</strong>
                        <span>{{ ucfirst($currentAdmin->role) }}</span>
                        </span>
                    </div>
                @endif
            </div>

            @include('admin.partials.flash')

            @yield('content')
        </main>
    </div>
    @if (($currentAdmin ?? null))
        <script>
            window.updateAdminUnread = count => {
                document.querySelectorAll('[data-message-unread]').forEach(badge => {
                    badge.textContent = count;
                    badge.hidden = Number(count) < 1;
                });
            };
            window.setInterval(async () => {
                if (document.hidden) return;
                try {
                    const response = await fetch(@json(route('admin.messages.unread')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (response.ok) window.updateAdminUnread((await response.json()).unread_count);
                } catch (_) {}
            }, 15000);
        </script>
    @endif
    @stack('scripts')
</body>
</html>
