{{--
  The shell of the screens where somebody looks after their vaults and what is
  in them. It is not the settings shell: there is no column of links, because
  the vaults are the product rather than a corner of it, and what a screen needs
  to say about where you are fits in one line under the bar.

  The bar carries the mark, the way to the settings and who is signed in. The
  strip under it carries the breadcrumb, and a screen that has nowhere to come
  back from may leave it out.

  @var string|null $title
  @var \Illuminate\View\ComponentSlot|null $breadcrumb
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <header class="sticky top-0 z-5 flex h-13 items-center gap-2.5 border-b border-hairline bg-page/90 px-4 backdrop-blur-md sm:px-7">
      <x-logo :size="26" />

      <span class="truncate text-sm font-semibold tracking-tight text-ink">{{ config('app.name') }}</span>

      <div class="ml-auto flex items-center gap-2.5">
        <x-theme-toggle />

        <a
          href="{{ route('settings.profile.index') }}"
          data-turbo="true"
          aria-label="{{ __('Settings') }}"
          class="flex items-center rounded-md p-1.5 text-muted transition-colors hover:bg-hover hover:text-ink"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
            <circle cx="8" cy="8" r="2.2"></circle>
            <path d="M8 1.6v1.6M8 12.8v1.6M1.6 8h1.6M12.8 8h1.6M3.5 3.5l1.1 1.1M11.4 11.4l1.1 1.1M12.5 3.5l-1.1 1.1M4.6 11.4l-1.1 1.1"></path>
          </svg>
        </a>

        <x-avatar :user="auth()->user()" :size="26" />
      </div>
    </header>

    @isset($breadcrumb)
      <div class="border-b border-hairline bg-canvas">
        <div class="mx-auto max-w-240 px-4 py-2.5 sm:px-7">
          {{ $breadcrumb }}
        </div>
      </div>
    @endisset

    <main class="mx-auto max-w-240 space-y-8.5 px-4 pt-7.5 pb-22 sm:px-7">
      {{ $slot }}
    </main>

    <x-toaster />
  </body>
</html>
