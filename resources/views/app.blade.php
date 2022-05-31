<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title inertia>{{ config('app.name', 'Laravel') }}</title>

  <!-- Styles -->
  <link rel="stylesheet" href="{{ mix('css/app.css') }}" />

  <!-- Scripts -->
  @routes
  <script src="{{ mix('js/app.js') }}" defer></script>
</head>

<body class="font-sans antialiased bg-white dark:bg-gray-800 dark:text-gray-300">
  @inertia

  @env ('local')
  <script src="http://localhost:8080/js/bundle.js"></script>
  <script src="https://cdn.jsdelivr.net/gh/underground-works/clockwork-browser@1/dist/toolbar.js"></script>
  @endenv
</body>

</html>
