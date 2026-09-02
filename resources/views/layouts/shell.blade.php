{{--
  The shell of every screen somebody sees once they are signed in: a navigation
  column on the left that stays put, and the screen itself on the right. On a
  narrow screen the column slides in over the page instead, from the button in
  the bar.

  **The window never scrolls.** It is exactly as tall as the viewport, and the
  right hand side is the one thing that scrolls. That is the whole layout, and
  it is one layout rather than two: every screen behaves the same way, whatever
  it holds.

  It is written this way because the alternative kept breaking. When the page
  was what scrolled, anything that grew or shrank inside a screen moved the
  navigation column and the bar with it, a screen shorter than the window left
  the column stranded halfway up, and a form that revealed a control pushed
  everything under it past the fold. With the column outside the scroll and the
  screen inside it, none of those is possible: a section opening or closing
  changes what is inside the scroll region and nothing else on the page.

  For that to hold, the window has to be unable to scroll at all, which takes
  two things and not one: this layout is exactly the height of the window, and
  the region below is positioned, so nothing inside a screen can be laid out
  against the document and make it taller. The second is the easy one to lose.

  The bar at the top scrolls away with the screen rather than sticking to it. It
  says where you are, which is worth reading on arrival and not worth 56px of
  every screen thereafter, and a bar that stayed would cover whatever a screen
  sticks to the top of its own scroll region.

  `navOpen` is declared here, on the common parent of the column and the
  backdrop, and that parent is inside <body>: alpine is re-initialised from the
  body after a turbo navigation, so state declared above it would never be
  walked again.

  A screen whose actions have to stay reachable through a long form puts them in
  the `footer` slot. It is a bar of its own at the foot of the scroll region
  rather than a row at the end of the page, and it spans the screen the way the
  bar at the top does: what sits inside it lines up with the content column, but
  the rule and the background it draws run the whole width, or it reads as a
  strip that stopped short.

  A screen built around something too wide for the reading column asks for
  `wide`. It loses the 960px cap and spreads to whatever the window gives it,
  while keeping the same gutter every other screen has, because content that
  touches the edge of the window reads as content that has been cut off rather
  than as content that is wide. That is the whole of what `wide` does now: it
  used to arrange its own scrolling as well, and no longer has to, because every
  screen already scrolls in the one place.

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

      {{--
        The one scroll container on the screen. A screen carrying a footer is
        also a column, so the content between the bars grows and the footer is
        at the foot of a short screen instead of stranded halfway up it.

        While the drawer is open on a narrow screen, this stops scrolling. The
        scrim over it catches a click and not a wheel or a swipe, and the screen
        moving behind an open menu reads as two things scrolling at once. It is
        said here rather than on the body, which is not what scrolls.

        `relative` is load bearing and is the whole of what keeps the window from
        scrolling. An `sr-only` element is absolutely positioned, so without a
        positioned ancestor it is laid out against the document rather than
        against this region, and nothing here can clip what it does not contain.
        A few of them low down a long screen were enough to make the document
        taller than the window, and the window scrolling lifted this whole
        window-height layout, navigation column and all. One `relative` here
        settles it for every screen, rather than every screen having to know.
      --}}
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
