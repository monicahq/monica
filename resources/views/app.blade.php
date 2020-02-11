<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <base href="{{ url('/') }}/" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <link rel="stylesheet" href="{{ asset(mix('css/app.css')) }}">
  <script src="{{ mix('/js/app.js') }}" defer></script>
  <title>@yield('title', trans('app.application_title'))</title>

  @routes
</head>

<body data-account-id={{ Auth::check() ? auth()->user()->account_id : 0 }}>

  @inertia

</body>

</html>
