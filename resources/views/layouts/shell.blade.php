{{--
  The shell of every screen somebody sees once they are signed in: a navigation
  column on the left that stays put, and the screen itself on the right under a
  bar that says where you are. On a narrow screen the column slides in over the
  page instead, from the button in the bar.

  `navOpen` is declared here, on the common parent of the column and the
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
  `wide`. It loses the 960px cap and spreads to whatever the window gives it,
  while keeping the same gutter every other screen has, because content that
  touches the edge of the window reads as content that has been cut off rather
  than as content that is wide. The screen also becomes exactly as tall as the
  window, which is what lets it hand the scrolling to a region of its own: a table
  whose header stays put while its rows move needs the rows, and not the page, to
  be the thing that scrolls. That region is what runs to the full width, inside
  the gutter.

  The column is a slot rather than something this layout builds, because which
  vault it points at and which destination is current are the screen's business,
  not the shell's. A screen that leaves it out gets the whole window instead of a
  248px strip of nothing, but every signed in screen has somewhere to navigate, so
  leaving it out is a decision rather than an oversight.

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
      @class(['grid min-h-screen grid-cols-1', 'lg:grid-cols-[248px_minmax(0,1fr)]' => isset($sidebar)])
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

      {{--
        A screen carrying a footer becomes a column, so the content between the
        two bars grows and the footer is at the foot of a short screen instead of
        stranded halfway up it. Screens without one are left exactly as they were.
      --}}
      <main @class(['min-w-0', 'flex flex-col' => isset($footer), 'flex h-dvh flex-col' => $wide])>
        <header class="sticky top-0 z-5 flex h-14 items-center gap-3.5 border-b border-topbar-hairline bg-topbar/85 px-4 backdrop-blur-[8px] sm:px-6">
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

          {{--
            A screen with something to do from the bar puts it here. A screen with
            nothing leaves the space empty: who is signed in is at the foot of the
            navigation column, and the bar repeating it said the same thing twice.
          --}}
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
            'mx-auto flex min-h-0 w-full max-w-350 flex-1 flex-col gap-4' => $wide,
            'flex-1 pb-8' => isset($footer),
            'pb-6 sm:pb-7' => ! isset($footer) && $wide,
            'pb-14 sm:pb-18' => ! isset($footer) && ! $wide,
          ])
        >
          {{ $slot }}
        </div>

        @isset($footer)
          <footer class="sticky bottom-0 z-5 border-t border-hairline bg-canvas">
            <div class="mx-auto flex max-w-240 flex-wrap items-center gap-4 px-4 py-3 sm:px-6">
              {{ $footer }}
            </div>
          </footer>
        @endisset
      </main>
    </div>

    <x-toaster />
  </body>
</html>
