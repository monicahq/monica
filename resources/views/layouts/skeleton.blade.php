<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>Monica</title>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <link rel="stylesheet" href="{{ elixir('css/app.css') }}">
    <link rel="shortcut icon" href="/img/favicon.png">
  </head>
  <body>

    @include('partials.header')

    <div id="app">
      @yield('content')
    </div>

    @include('partials.footer')

    <script src="//cdnjs.cloudflare.com/ajax/libs/list.js/1.5.0/list.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/trix/0.10.1/trix-core.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/trix/0.10.1/trix.js"></script>
    <script>
      var options = {
        valueNames: [ 'people-list-item-name' ]
      };
      var userList = new List('search-list', options);
    </script>
    <script src="{{ elixir('js/app.js') }}"></script>
    <script>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

      ga('create', 'UA-86013324-1', 'auto');
      ga('send', 'pageview');

    </script>
  </body>
</html>
