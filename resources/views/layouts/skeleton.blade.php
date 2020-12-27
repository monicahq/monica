<!DOCTYPE html>
<html lang="{{ \App::getLocale() }}" dir="{{ htmldir() }}">
  <head>
    <base href="{{ url('/') }}/" />
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>@yield('title', trans('app.application_title'))</title>
    <link rel="manifest" href="manifest.webmanifest">

    <link rel="stylesheet" href="{{ asset(mix('css/app-'.htmldir().'.css')) }}">
    {{-- Required only for the Upgrade account page --}}
    @if (Route::currentRouteName() == 'settings.subscriptions.upgrade' || Route::currentRouteName() == 'settings.subscriptions.confirm')
      <link rel="stylesheet" href="{{ asset(mix('css/stripe.css')) }}">
    @endif

    <link rel="shortcut icon" href="img/favicon.png">
    <link rel="apple-touch-icon" sizes="57x57" href="img/icons/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="img/icons/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="img/icons/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="img/icons/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="img/icons/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="img/icons/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="img/icons/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="img/icons/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="img/icons/apple-icon-180x180.png">

    <script>
      window.Laravel = {!! \Safe\json_encode([
          'locale' => \App::getLocale(),
          'htmldir' => htmldir(),
          'profileDefaultView' => auth()->user()->profile_active_tab,
          'timezone' => auth()->user()->timezone,
          'env' => \App::environment(),
      ]); !!}
    </script>
  </head>
  <body data-account-id="{{ auth()->user()->account_id }}" class="bg-gray-monica min-vh-100 flex flex-column">

    <div id="app" class="flex-grow-1">
      @if (Route::currentRouteName() != 'settings.subscriptions.confirm')
        @include('partials.header')
        @include('partials.subscription')
      @endif
      @yield('content')
    </div>

    @if (Route::currentRouteName() != 'settings.subscriptions.confirm')
      @include('partials.footer')
    @endif

    {{-- THE JS FILE OF THE APP --}}
    @push('scripts')
      <script src="{{ asset(mix('js/manifest.js')) }}"></script>
      <script src="{{ asset(mix('js/vendor.js')) }}"></script>
    @endpush

    {{-- Load everywhere except on the Upgrade account page --}}
    @if (Route::currentRouteName() != 'settings.subscriptions.upgrade' && Route::currentRouteName() != 'settings.subscriptions.confirm')
      @push('scripts')
        <script src="{{ asset(mix('js/app.js')) }}"></script>
      @endpush
    @endif

    @stack('scripts')

  </body>
</html>
