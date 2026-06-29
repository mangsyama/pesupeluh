<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png?v=2">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png?v=2">
        <link rel="shortcut icon" href="/favicon.ico?v=2">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=2">

        <!-- PWA Manifest -->
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#ffffff">

        <script>
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>

        <!-- Scripts -->
        @routes
        {{-- Include the main Vite entry only; pages are bundled via import.meta.glob in `resources/js/app.js` --}}
        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-slate-950">
        @inertia
    </body>
</html>
