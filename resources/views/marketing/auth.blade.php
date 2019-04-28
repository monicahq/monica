<!DOCTYPE html>
<html lang="{{ \App::getLocale() }}" dir="{{ htmldir() }}">
  <head>
    <base href="{{ url('/') }}/" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>@yield('title', trans('app.application_title'))</title>
    <link rel="manifest" href="manifest.webmanifest">

    <link rel="stylesheet" href="{{ asset(mix('css/app-'.htmldir().'.css')) }}">
    <link rel="shortcut icon" href="img/favicon.png">
    <script>
      window.Laravel = {!! \Safe\json_encode([
          'csrfToken' => csrf_token(),
          'locale' => \App::getLocale(),
          'htmldir' => htmldir(),
      ]); !!}
    </script>
  </head>

  <body data-account-id={{ auth()->user()->account_id }} class="marketing register bg-gray-monica">

      <div id="app">
        @yield('content')
      </div>

    {{-- THE JS FILE OF THE APP --}}
      <script src="{{ asset(mix('js/manifest.js')) }}"></script>
      <script src="{{ asset(mix('js/vendor.js')) }}"></script>
      <script src="{{ asset(mix('js/app.js')) }}"></script>

    @stack('scripts')

  </body>
</html>
