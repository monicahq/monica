{{-- The charset, the viewport and the csrf token every layout opens its <head> with. --}}
{{--
  @var string|null $title
  @var string|null $description
--}}

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

@if (request()->hasSession())
  <meta name="csrf-token" content="{{ csrf_token() }}" />
@endif

<title>{{ $title ?? config('app.name') }}</title>

@if (! empty($description))
  <meta name="description" content="{{ $description }}" />
@endif

<link rel="icon" type="image/png" href="{{ asset('images/favicon/favicon-96x96.png') }}" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon/favicon.svg') }}" />
<link rel="shortcut icon" href="{{ asset('images/favicon/favicon.ico') }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}" />
<link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}" />

<script>
  (function () {
    try {
      var theme = localStorage.getItem('theme');

      if (theme === 'dark' || (! theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
      }
    } catch (e) {}
  })();
</script>
