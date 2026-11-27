{{-- What every layout opens its <head> with, short of the <title>: charset, csrf token, robots, icons. --}}
{{--
  @var string|null $title
  @var string|null $description
  @var bool|null $error
--}}

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />

@if (request()->hasSession())
  <meta name="csrf-token" content="{{ csrf_token() }}" />
@endif

<meta name="turbo-cache-control" content="no-cache" />

@php
  if (isset($title)) {
      \Laravel\Head\Facades\Head::title($title, exact: true);
  }

  if (! empty($description)) {
      \Laravel\Head\Facades\Head::description($description);
  }

  if (request()->attributes->get('searchable') && empty($error)) {
      // The request's own scheme: a self hosted instance served over http has no https address.
      \Laravel\Head\Facades\Head::canonical(forceHttps: false);
  }
@endphp

{!! implode(PHP_EOL, array_filter(\Laravel\Head\Facades\Head::toElements(), fn (string $element): bool => ! str_starts_with($element, '<title>'))) !!}

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
