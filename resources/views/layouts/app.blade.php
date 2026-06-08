<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title>@yield('title', 'VR/AR Meeting Rooms')</title>
    <meta name="description" content="Explore architectural meeting rooms in immersive VR and AR — straight from your browser.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    @stack('head')
</head>
<body>
    @yield('body')
    @stack('scripts')
</body>
</html>
