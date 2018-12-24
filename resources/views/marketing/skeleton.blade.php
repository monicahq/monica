<!DOCTYPE html>
<html lang="{{ \App::getLocale() }}" dir="{{ htmldir() }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="IE=edge,chrome=1">
    <title>{{ trans('app.application_title') }}</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset(mix('css/app-'.htmldir().'.css')) }}">
    <meta name="description" content="{{ trans('app.application_description') }}">
    <link rel="author" href="@djaiss">
    <meta property="og:title" content="{{ trans('app.application_og_title') }}" />
    <link rel="shortcut icon" href="{{ asset('/img/favicon.png') }}">
  </head>

  @yield('content')

</html>
