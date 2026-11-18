{{-- The shell of every signed-in screen: the navigation column, and the screen beside it. --}}
{{--
  @var string|null $title
  @var bool $wide
  @var \App\Models\Vault|null $searchVault
  @var \App\ViewModels\FullAccessOfferViewModel|null $fullAccessOffer
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
      @class(['grid h-dvh grid-cols-1 overflow-hidden print:block print:h-auto print:overflow-visible', 'lg:grid-cols-[248px_minmax(0,1fr)]' => isset($sidebar)])
    >
      @isset($sidebar)
        {{ $sidebar }}

        <x-nav-scrim />
      @endisset

      <main
        x-bind:class="navOpen && 'max-lg:overflow-hidden'"
        @class(['relative min-w-0 overflow-y-auto print:overflow-visible', 'flex flex-col' => isset($footer)])
      >
        <header class="flex h-14 shrink-0 items-center gap-2 sm:gap-3.5 border-b border-topbar-hairline bg-topbar px-4 sm:px-6 print:hidden">
          <x-menu-button />

          @isset($breadcrumb)
            <div class="-m-1 min-w-0 overflow-hidden p-1">
              {{ $breadcrumb }}
            </div>
          @endisset

          <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2.5">
            @if ($searchVault !== null)
              <form method="get" action="{{ route('search.index', $searchVault) }}" role="search" class="hidden md:block">
                <label for="shell-search" class="sr-only">{{ __('Search this vault') }}</label>
                <input
                  id="shell-search"
                  type="search"
                  name="query"
                  placeholder="{{ __('Search') }}"
                  class="h-8 w-56 rounded-md border border-hairline-strong bg-input px-3 text-sm text-ink placeholder:text-placeholder focus-ring-field"
                />
              </form>

              <a
                href="{{ route('search.index', $searchVault) }}"
                data-turbo="true"
                aria-label="{{ __('Search this vault') }}"
                class="flex size-9 items-center justify-center rounded-md text-muted hover:bg-hover hover:text-ink focus-ring md:hidden"
              >
                <x-icon name="search" :size="17" />
              </a>
            @endif

            @isset($actions)
              {{ $actions }}
            @endisset

            <x-bar-links :settings="false" />
          </div>
        </header>

        @if ($fullAccessOffer !== null)
          <x-full-access-offer :viewModel="$fullAccessOffer" />
        @endif

        <div
          @class([
            'w-full px-4 pt-6 sm:px-6 sm:pt-7 print:max-w-none print:p-0',
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
          <footer class="sticky print:hidden bottom-0 z-5 shrink-0 border-t border-hairline bg-canvas">
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
