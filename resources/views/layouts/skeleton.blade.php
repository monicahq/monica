<!DOCTYPE html>
<html lang="{{ \App::getLocale() }}" dir="{{ \App\Helpers\LocaleHelper::getDirection() }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>@yield('title', trans('app.application_title'))</title>
    <link rel="manifest" href="/manifest.webmanifest">

    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <link rel="shortcut icon" href="/img/favicon.png">
    <script>
      window.Laravel = {!! json_encode([
          'csrfToken' => csrf_token(),
          'locale' => \App::getLocale()
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
      <script src="{{ mix('js/manifest.js') }}"></script>
      <script src="{{ mix('js/vendor.js') }}"></script>
      <script src="{{ mix('js/app.js') }}"></script>
    @endif

    {{-- Required only for the Upgrade account page --}}
    @if (Route::currentRouteName() == 'settings.subscriptions.upgrade')
      <script src="https://js.stripe.com/v3/"></script>
      <script>
        var stripe = Stripe('{{config('services.stripe.key')}}');
      </script>
      <script src="{{ mix('js/manifest.js') }}"></script>
      <script src="{{ mix('js/stripe.js') }}"></script>
      <link rel="stylesheet" href="{{ mix('css/stripe.css') }}">
    @endif

    @stack('scripts')

  </body>
</html>
