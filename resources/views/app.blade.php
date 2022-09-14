<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <link rel="shortcut icon" href="img/favicon.svg">

    <!-- Scripts -->
    @if (app()->bound('sentry') && config('sentry.dsn') !== null)
    <script type="text/javascript">
      const SentryConfig = {!! \json_encode([
        'dsn' => config('sentry.dsn'),
        'environment' => config('sentry.environment'),
        'sendDefaultPii' => config('sentry.send_default_pii'),
        'tracesSampleRate' => config('sentry.traces_sample_rate'),
      ]); !!}
    </script>
    @endif

    <script type="text/javascript">
      if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark')
      } else {
        document.documentElement.classList.remove('dark')
      }
    </script>

    @routes
    @vite('resources/js/app.js')
    @inertiaHead
  </head>

  <body class="font-sans antialiased bg-white dark:bg-gray-800 dark:text-gray-300">
    @inertia
  </body>

</html>
