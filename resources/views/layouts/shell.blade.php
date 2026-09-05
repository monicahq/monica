{{-- The shell of every signed-in screen: the navigation column, and the screen beside it. --}}
{{--
  @var string|null $title
  @var bool $wide
  @var \Illuminate\View\ComponentSlot $sidebar
  @var \Illuminate\View\ComponentSlot|null $breadcrumb
  @var \Illuminate\View\ComponentSlot|null $actions
  @var \Illuminate\View\ComponentSlot|null $footer
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div
      x-data="{ navOpen: false }"
      @keydown.escape.window="navOpen = false"
      @class(['grid h-dvh grid-cols-1 overflow-hidden', 'lg:grid-cols-[248px_minmax(0,1fr)]' => isset($sidebar)])
    >
      @isset($sidebar)
        {{ $sidebar }}

        <div
          x-cloak
          x-show="navOpen"
          x-transition.opacity.duration.150ms
          @click="navOpen = false"
          class="fixed inset-0 z-30 bg-scrim lg:hidden"
          aria-hidden="true"
        ></div>
      @endisset

      <main
        x-bind:class="navOpen && 'max-lg:overflow-hidden'"
        @class(['relative min-w-0 overflow-y-auto', 'flex flex-col' => isset($footer)])
      >
        <header class="flex h-14 shrink-0 items-center gap-3.5 border-b border-topbar-hairline bg-topbar px-4 sm:px-6">
          <button
            type="button"
            @click="navOpen = true"
            :aria-expanded="navOpen ? 'true' : 'false'"
            aria-controls="main-nav"
            aria-label="{{ __('Open the menu') }}"
            class="-ml-1.5 flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none lg:hidden"
          >
            <x-icon name="menu" :size="17" :stroke-width="1.4" />
          </button>

          @isset($breadcrumb)
            {{ $breadcrumb }}
          @endisset

          <div class="ml-auto flex min-w-0 items-center gap-2.5">
            @isset($actions)
              {{ $actions }}
            @endisset

            <x-bar-links :settings="false" />
          </div>
        </header>

        <div
          @class([
            'w-full px-4 pt-6 sm:px-6 sm:pt-7',
            'mx-auto max-w-240 space-y-8' => ! $wide,
            'mx-auto flex w-full max-w-350 flex-col gap-4' => $wide,
            'flex-1 pb-8' => isset($footer),
            'pb-6 sm:pb-7' => ! isset($footer) && $wide,
            'pb-14 sm:pb-18' => ! isset($footer) && ! $wide,
          ])
        >
          {{ $slot }}
        </div>

        @isset($footer)
          <footer class="sticky bottom-0 z-5 shrink-0 border-t border-hairline bg-canvas">
            <div
              @class([
                'mx-auto flex flex-wrap items-center gap-4 px-4 py-3 sm:px-6',
                'max-w-240' => ! $wide,
                'w-full max-w-350' => $wide,
              ])
            >
              {{ $footer }}
            </div>
          </footer>
        @endisset
      </main>
    </div>

    <x-toaster />
  </body>
</html>
