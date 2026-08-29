{{--
  The shell of the screens that stand outside any one vault: the list of them,
  the form that opens one, and the screen somebody lands on before they have
  made anything. There is no column of links, because none of those screens is
  inside anything to navigate, and what they need to say about where you are
  fits in one line under the bar. Once you are in a vault the sidebar shell
  takes over, with the vault's own navigation down the left.

  The bar carries the mark, the way out to the docs and the marketplace, the way
  to the settings, and the light and dark switch. It does not say who is signed
  in: an avatar with nothing behind it is decoration, and the one place that
  answer belongs is the foot of the navigation column, where a click on it does
  something. The strip under the bar carries the breadcrumb, and a screen that
  has nowhere to come back from may leave it out.

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
    <header class="sticky top-0 z-5 flex h-14 items-center gap-2.5 border-b border-topbar-hairline bg-topbar/85 px-4 backdrop-blur-[8px] sm:px-6">
      <x-logo :size="26" />

      <span class="truncate text-sm font-semibold tracking-tight text-ink">{{ config('app.name') }}</span>

      <div class="ml-auto flex items-center gap-2.5">
        <x-bar-links />

        <x-theme-toggle />
      </div>
    </header>

    @isset($breadcrumb)
      <div class="border-b border-hairline bg-canvas">
        <div class="mx-auto max-w-240 px-4 py-2.5 sm:px-6">
          {{ $breadcrumb }}
        </div>
      </div>
    @endisset

    {{--
      A screen with a breadcrumb already has a strip between it and the bar, so
      it starts closer to the top than one that opens on its own title.
    --}}
    <main class="mx-auto max-w-240 space-y-8 px-4 pb-22 sm:px-6 {{ isset($breadcrumb) ? 'pt-7.5' : 'pt-14' }}">
      {{ $slot }}
    </main>

    <x-toaster />
  </body>
</html>
