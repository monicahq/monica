{{--
  The shell of a screen navigated by the dock.

  It is deliberately thin. There is a bar at the top that says where you are and
  who is signed in, a single column of content in the middle, and the dock at the
  foot of the window. There is no column of links down the left: that is what the
  dock replaced.

  The column is capped at the reading width, because what most of these screens hold
  is sentences and rows of them, and a line of text that runs the width of a monitor
  is a line nobody finishes.

  A screen built around something wider asks for `wide` and gets the content width
  instead. That is for a screen with two columns of its own, such as the template
  builder, and not for making a form roomier: a form at 1120px is a form whose
  labels are a long way from their fields.

  The bottom padding is what keeps the last row clear of the dock. The dock is
  fixed, so it takes no space in the flow and would otherwise sit on top of the
  content; 120px is its 72 plus the 18 it floats above the edge plus room to
  breathe.

  The dock is a slot rather than something this layout builds, because which vault
  it points at and which destination is current are the screen's business, not the
  shell's. A screen may leave it out: the vault list does, because the dock points
  into one vault and that screen is where you choose between them.

  A screen with no dock takes its bottom padding back. The 120px is only there to
  keep the last row clear of something that is floating over it.

  `bar` is the left of the top bar and `barEnd` is the right of it, before the theme
  toggle. A screen reaches its own settings from the launcher behind the dock, so
  `barEnd` is for the one screen that has no dock to reach them from.

  @var string|null $title
  @var bool $wide
  @var \Illuminate\View\ComponentSlot|null $bar
  @var \Illuminate\View\ComponentSlot|null $barEnd
  @var \Illuminate\View\ComponentSlot|null $dock
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <header class="sticky top-0 z-5 flex h-15 items-center gap-3.5 border-b border-hairline-strong bg-canvas px-4 shadow-bar sm:px-6">
      @isset($bar)
        {{ $bar }}
      @endisset

      <div class="ml-auto flex items-center gap-2.5">
        @isset($barEnd)
          {{ $barEnd }}
        @endisset

        <x-theme-toggle />

        {{-- The id lets a form that changes somebody's name refresh this in place. --}}
        <x-avatar id="shell-avatar" :user="auth()->user()" :size="32" />
      </div>
    </header>

    <main
      @class([
        'mx-auto flex w-full flex-col gap-8 px-4 pt-11 sm:px-6',
        'max-w-180' => ! $wide,
        'max-w-280' => $wide,
        'pb-30' => isset($dock),
        'pb-14' => ! isset($dock),
      ])
    >
      {{ $slot }}
    </main>

    @isset($dock)
      {{ $dock }}
    @endisset

    <x-toaster />
  </body>
</html>
