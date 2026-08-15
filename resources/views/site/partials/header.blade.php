<header class="header">
    <div class="container">
        <div class="header-content">
            <a href="{{ route('site.home') }}#home" class="logo">
                <div class="logo-icon">
                    <img src="{{ asset('images/ICSA-LOGO.png') }}" alt="ICSA Logo">
                </div>
                <div class="logo-text">
                    <span class="logo-title">ICSA</span>
                    <span class="logo-subtitle">International Institute of Computer Science and Administration</span>
                </div>
            </a>

            <nav class="nav">
                <a href="{{ route('site.home') }}#home" class="nav-link {{ request()->routeIs('site.home') ? 'active' : '' }}">Home</a>
                <a href="{{ route('site.home') }}#about" class="nav-link">About Us</a>
                <a href="{{ route('site.home') }}#courses" class="nav-link {{ request()->routeIs('site.course') ? 'active' : '' }}">Courses</a>
                <a href="{{ route('site.home') }}#contact" class="nav-link">Contact</a>
            </nav>

            <div class="header-actions">
                @if ($showHeaderLogin)
                    <a href="{{ config('services.student_portal.url') }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener">Login</a>
                @endif
                <a href="{{ route('site.home') }}#courses" class="btn btn-primary btn-sm">Enroll Now</a>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</header>
