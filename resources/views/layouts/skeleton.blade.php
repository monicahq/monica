<!DOCTYPE html>
<html lang="{{ \App::getLocale() }}" dir="{{ htmldir() }}">
  <head>
    <base href="{{ url('/') }}/" />

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="apple-mobile-web-app-title" content="Monica">
    <meta name="application-name" content="Monica">
    <meta name="theme-color" content="#325776">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', trans('app.application_title'))</title>

    <link rel="manifest" href="manifest.webmanifest">

    <link rel="stylesheet" href="{{ asset(mix('css/app.css')) }}">
    <link rel="shortcut icon" href="img/favicon.png">

    <link rel="apple-touch-icon" href="img/icons/touch-icon-iphone.png">
    <link rel="apple-touch-icon" sizes="152x152" href="img/icons/touch-icon-ipad.png">
    <link rel="apple-touch-icon" sizes="180x180" href="img/icons/touch-icon-iphone-retina.png">
    <link rel="apple-touch-icon" sizes="167x167" href="img/icons/touch-icon-ipad-retina.png">

    <link rel="shortcut icon" sizes="196x196" href="img/icons/favicon-196.png">
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

  </body>
</html>
