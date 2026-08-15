<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ICSA Kuwait')</title>
    <meta name="description" content="@yield('description', 'ICSA Kuwait professional education and training.')">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    @include('site.partials.topbar')
    @include('site.partials.header', ['showHeaderLogin' => $showHeaderLogin ?? false])

    @yield('content')

    @include('site.partials.footer')

    <script src="{{ asset('js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
