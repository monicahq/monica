{{--
  The shell of every screen somebody sees once they are signed in: a sidebar on
  the left that stays put, and the screen itself on the right under a bar that
  says where you are. On a narrow screen the sidebar slides in over the page
  instead, from the button in the bar.

  `navOpen` is declared here, on the common parent of the sidebar and the
  backdrop, and that parent is inside <body>: alpine is re-initialised from the
  body after a turbo navigation, so state declared above it would never be
  walked again.

  A screen whose actions have to stay reachable through a long form puts them
  in the `footer` slot. It is a bar of its own at the foot of the window rather
  than a row at the end of the page, and it spans the screen the way the bar at
  the top does: what sits inside it lines up with the content column, but the
  rule and the background it draws run the whole width, or it reads as a strip
  that stopped short.

  A screen built around something too wide for the reading column asks for
  `wide`. It loses the column and the padding either side of it and pads its own
  header instead, so a table can run to both edges of the window. The screen also
  becomes exactly as tall as the window, which is what lets it hand the scrolling
  to a region of its own: a table whose header stays put while its rows move needs
  the rows, and not the page, to be the thing that scrolls.

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
      x-effect="document.body.classList.toggle('overflow-hidden', navOpen)"
      class="grid min-h-screen grid-cols-1 lg:grid-cols-[264px_minmax(0,1fr)]"
    >
      {{ $sidebar }}

      <div
        x-cloak
        x-show="navOpen"
        x-transition.opacity.duration.150ms
        @click="navOpen = false"
        class="fixed inset-0 z-30 bg-black/40 lg:hidden"
        aria-hidden="true"
      ></div>

      {{--
        A screen carrying a footer becomes a column, so the content between the
        two bars grows and the footer is at the foot of a short screen instead of
        stranded halfway up it. Screens without one are left exactly as they were.
      --}}
      <main @class(['min-w-0', 'flex flex-col' => isset($footer), 'flex h-dvh flex-col' => $wide])>
        <header class="sticky top-0 z-5 flex h-13 items-center gap-3.5 border-b border-hairline bg-page/90 px-4 backdrop-blur-md sm:px-7">
          <button
            type="button"
            @click="navOpen = true"
            :aria-expanded="navOpen ? 'true' : 'false'"
            aria-controls="settings-nav"
            aria-label="{{ __('Open the menu') }}"
            class="-ml-1.5 flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-md lg:hidden text-muted transition-colors hover:bg-hover hover:text-ink"
          >
            <svg width="17" height="17" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">
              <line x1="2.4" y1="4.4" x2="13.6" y2="4.4"></line>
              <line x1="2.4" y1="8" x2="13.6" y2="8"></line>
              <line x1="2.4" y1="11.6" x2="13.6" y2="11.6"></line>
            </svg>
          </button>

          @isset($breadcrumb)
            {{ $breadcrumb }}
          @endisset

          {{--
            A screen with something to do from the bar puts it here. The ones
            that have nothing say who is signed in instead, which is what the
            settings screens have always shown there.
          --}}
          @isset($actions)
            <div class="ml-auto flex min-w-0 items-center gap-2.5">
              {{ $actions }}
            </div>
          @else
            <span class="ml-auto hidden sm:block text-sm text-muted">{{ auth()->user()?->email }}</span>
          @endisset
        </header>

        <div
          @class([
            'w-full pt-6 sm:pt-7.5',
            'mx-auto max-w-240 space-y-8.5 px-4 sm:px-7' => ! $wide,
            'flex min-h-0 flex-1 flex-col gap-4' => $wide,
            'flex-1 pb-8' => isset($footer),
            'pb-14 sm:pb-17.5' => ! isset($footer) && ! $wide,
          ])
        >
          {{ $slot }}
        </div>

        @isset($footer)
          <footer class="sticky bottom-0 z-5 border-t border-hairline bg-canvas">
            <div class="mx-auto flex max-w-240 flex-wrap items-center gap-4 px-4 py-3 sm:px-7">
              {{ $footer }}
            </div>
          </footer>
        @endisset
      </main>
    </div>

    <x-toaster />
  </body>
</html>
