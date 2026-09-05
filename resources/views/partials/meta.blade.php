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

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32" />

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
