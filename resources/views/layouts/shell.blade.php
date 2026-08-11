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
  shell's.

  @var string|null $title
  @var bool $wide
  @var \Illuminate\View\ComponentSlot|null $bar
  @var \Illuminate\View\ComponentSlot|null $dock
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <header class="sticky top-0 z-5 flex h-15 items-center gap-3.5 border-b border-hairline bg-page/90 px-4 backdrop-blur-md sm:px-6">
      @isset($bar)
        {{ $bar }}
      @endisset

      <div class="ml-auto flex items-center gap-2.5">
        <x-theme-toggle />

        {{-- The id lets a form that changes somebody's name refresh this in place. --}}
        <x-avatar id="shell-avatar" :user="auth()->user()" :size="32" />
      </div>
    </header>

    <main
      @class([
        'mx-auto flex w-full flex-col gap-8 px-4 pt-11 pb-30 sm:px-6',
        'max-w-180' => ! $wide,
        'max-w-280' => $wide,
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
