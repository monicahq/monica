<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    @if (app()->bound('sentry') && config('sentry.dsn') !== null)
    <script>
      const SentryConfig = {!! \json_encode([
        'dsn' => config('sentry.dsn'),
        'environment' => config('sentry.environment'),
        'sendDefaultPii' => config('sentry.send_default_pii'),
        'tracesSampleRate' => config('sentry.traces_sample_rate'),
      ]); !!}
    </script>
    @endif

    @routes
    @vite('resources/js/app.js')
    @inertiaHead
  </head>

  <body class="font-sans antialiased bg-white dark:bg-gray-800 dark:text-gray-300">
    @inertia
  </body>

</html>
