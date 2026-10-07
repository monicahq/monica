{{-- The shell of every screen a visitor sees before they sign in. --}}
{{-- @var string|null $title --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @if (config('turnstile.enabled'))
      <meta name="turbo-visit-control" content="reload" />
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    {{ $slot }}
  </body>
</html>
