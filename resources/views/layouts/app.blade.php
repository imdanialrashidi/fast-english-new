<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1D4ED8">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/apple-touch-180.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>{{ config('app.name', 'Fast English') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pwa.js'])
    @livewireStyles
</head>
<body>
<main>
    @yield('content')
</main>
@livewireScripts
</body>
</html>
