{{--
  The shell of the screens that stand outside any one vault: the list of them,
  the form that opens one, and the screen somebody lands on before they have
  made anything. There is no column of links, because none of those screens is
  inside anything to navigate, and what they need to say about where you are
  fits in one line under the bar. Once you are in a vault the sidebar shell
  takes over, with the vault's own navigation down the left.

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
          href="{{ route('settings.index') }}"
          data-turbo="true"
          aria-label="{{ __('Settings') }}"
          class="flex items-center rounded-md p-1.5 text-muted transition-colors hover:bg-hover hover:text-ink"
        >
          <x-icon name="settings" :size="16" />
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

    {{--
      A screen with a breadcrumb already has a strip between it and the bar, so
      it starts closer to the top than one that opens on its own title.
    --}}
    <main class="mx-auto max-w-240 space-y-8.5 px-4 pb-22 sm:px-7 {{ isset($breadcrumb) ? 'pt-7.5' : 'pt-14' }}">
      {{ $slot }}
    </main>

    <x-toaster />
  </body>
</html>
