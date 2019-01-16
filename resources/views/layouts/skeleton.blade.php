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
    {{-- Required only for the Upgrade account page --}}
    @if (Route::currentRouteName() == 'settings.subscriptions.upgrade')
      <link rel="stylesheet" href="{{ asset(mix('css/stripe.css')) }}">
    @endif

    <link rel="shortcut icon" href="img/favicon.png">
    <script>
      window.Laravel = {!! json_encode([
          'csrfToken' => csrf_token(),
          'locale' => \App::getLocale(),
          'htmldir' => htmldir(),
          'profileDefaultView' => auth()->user()->profile_active_tab,
      ]); !!}
    </script>
  </head>
  <body data-account-id={{ auth()->user()->account_id }} class="bg-gray-monica">

    @include('partials.header')

    <div id="app">
      @yield('content')
    </div>

    @include('partials.footer')

    {{-- THE JS FILE OF THE APP --}}
    {{-- Load everywhere except on the Upgrade account page --}}
    @if (Route::currentRouteName() != 'settings.subscriptions.upgrade')
      <script src="{{ asset(mix('js/manifest.js')) }}"></script>
      <script src="{{ asset(mix('js/vendor.js')) }}"></script>
      <script src="{{ asset(mix('js/app.js')) }}"></script>
    @endif

    {{-- Required only for the Upgrade account page --}}
    @if (Route::currentRouteName() == 'settings.subscriptions.upgrade')
      <script src="https://js.stripe.com/v3/"></script>
      <script>
        var stripe = Stripe('{{config('services.stripe.key')}}');
      </script>
      <script src="{{ asset(mix('js/manifest.js')) }}"></script>
      <script src="{{ asset(mix('js/stripe.js')) }}"></script>
    @endif

    @stack('scripts')

  </body>
</html>
