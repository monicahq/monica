<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ htmldir() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Monica') }}</title>

        <link rel="shortcut icon" href="/img/favicon.svg">

        <!-- Scripts -->
        @vite(['resources/js/theme.js'])

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
        @inertia
    </body>
</html>
