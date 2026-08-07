{{--
  Every layout opens its <head> with this partial, so the charset, the viewport and the
  csrf token live here and nowhere else. A layout that repeats them ships them twice.

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

{{-- Apply the saved theme before paint, so the page never flashes the wrong one. --}}
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
