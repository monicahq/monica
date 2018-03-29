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
      <script src="{{ mix('js/app.js') }}"></script>
    @endif

    {{-- Required only for the Upgrade account page --}}
    @if (Route::currentRouteName() == 'settings.subscriptions.upgrade')
      <script src="https://js.stripe.com/v3/"></script>
      <script>
        var stripe = Stripe('{{config('services.stripe.key')}}');
      </script>
      <script src="{{ mix('js/stripe.js') }}"></script>
      <link rel="stylesheet" href="{{ mix('css/stripe.css') }}">
    @endif

    {{-- TRACKING SHIT --}}
    @if(config('app.env') != 'local' && !empty(config('monica.google_analytics_app_id')))
      <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

        ga('create', '{{ config('monica.google_analytics_app_id') }}', 'auto');
        ga('send', 'pageview');
      </script>
    @endif

    @if (config('app.env') != 'local' && !empty(config('monica.intercom_app_id')))
      <script>
        window.intercomSettings = {
          app_id: "{{ config('monica.intercom_app_id') }}",
          user_id: {{ \Auth::user()->id }},
          name: "{{ \Auth::user()->first_name.' '.\Auth::user()->last_name }}",
          email: "{{ \Auth::user()->email }}",
          created_at: {{ \Auth::user()->created_at->timestamp }}
        };
      </script>
      <script>(function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/{{ config('monica.intercom_app_id') }}';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})()
      </script>
    @endif

    @stack('scripts')

  </body>
</html>
