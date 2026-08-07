{{--
  The shell of every screen somebody sees once they are signed in: a sidebar on
  the left that stays put, and the screen itself on the right under a bar that
  says where you are. On a narrow screen the sidebar slides in over the page
  instead, from the button in the bar.

  `navOpen` is declared here, on the common parent of the sidebar and the
  backdrop, and that parent is inside <body>: alpine is re-initialised from the
  body after a turbo navigation, so state declared above it would never be
  walked again.

  @var string|null $title
  @var \Illuminate\View\ComponentSlot $sidebar
  @var \Illuminate\View\ComponentSlot|null $breadcrumb
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

      <main class="min-w-0">
        <header class="sticky top-0 z-5 flex h-13 items-center gap-3.5 border-b border-hairline bg-page/90 px-4 backdrop-blur-md sm:px-7">
          <button
            type="button"
            @click="navOpen = true"
            :aria-expanded="navOpen ? 'true' : 'false'"
            aria-controls="settings-nav"
            aria-label="{{ __('Open the menu') }}"
            class="-ml-1.5 flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted transition-colors hover:bg-hover hover:text-ink lg:hidden"
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

          <span class="ml-auto hidden text-sm text-muted sm:block">{{ auth()->user()?->email }}</span>
        </header>

        <div class="mx-auto max-w-240 space-y-8.5 px-4 pt-6 pb-14 sm:px-7 sm:pt-7.5 sm:pb-17.5">
          {{ $slot }}
        </div>
      </main>
    </div>

    <x-toaster />
  </body>
</html>
