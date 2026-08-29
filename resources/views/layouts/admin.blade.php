{{--
  The shell of the admin space, which is the area for whoever runs the instance.

  It is a layout of its own rather than the account shell with a flag, because an
  administrator has to know which of the two products they are in at a glance.
  Somebody deactivating a user believing they are editing their own profile is the
  failure this exists to prevent. There is no accent colour left to spend on it, so
  the bar is inverted instead: it is the primary colour rather than the page, in
  both themes. It says what the area is in words, and it carries a way out to the
  ordinary application on the right.

  There is no dock and no link into a vault. The dock points into one vault, and
  the admin space never reaches inside one: what an administrator sees is accounts,
  never the people documented in them. A layout with no way in is the cheapest
  place to enforce that.

  The navigation is a list rather than a set of anchors, so a screen arriving later
  is one entry rather than a new block of markup. `current` is the route name of the
  screen showing, which is the screen's business rather than the shell's.

  @var string|null $title
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <header class="sticky top-0 z-5 border-b border-hairline bg-primary text-on-primary">
      <div class="mx-auto flex h-14 max-w-280 items-center gap-3 px-4 sm:px-6">
        <x-logo :size="24" />

        <span class="truncate text-card-title text-on-primary">{{ __('Admin space') }}</span>

        <span class="hidden truncate text-xs text-on-primary/60 sm:inline">{{ __('You are looking at the instance, not at your own account.') }}</span>

        <div class="ml-auto flex items-center gap-2.5">
          <a
            href="{{ route('home.index') }}"
            data-turbo="true"
            class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-md border border-on-primary/20 px-3 text-sm font-medium whitespace-nowrap text-on-primary no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-on-primary/10 focus-visible:ring-[3px] focus-visible:ring-on-primary/40 focus-visible:outline-none"
          >
            <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
            {{ __('Leave the admin space') }}
          </a>

          <x-theme-toggle tone="inverted" />

          <x-avatar :user="auth()->user()" :size="26" />
        </div>
      </div>

      <nav class="mx-auto flex max-w-280 items-center gap-1 overflow-x-auto px-4 pb-2 sm:px-6">
        @foreach ([
          ['label' => __('Overview'), 'route' => 'admin.overview.index'],
          ['label' => __('Moderation'), 'route' => 'admin.moderation.index'],
          ['label' => __('Accounts'), 'route' => 'admin.accounts.index'],
          ['label' => __('Administrators'), 'route' => 'admin.administrators.index'],
          ['label' => __('Record'), 'route' => 'admin.logs.index'],
        ] as $item)
          <a
            href="{{ route($item['route']) }}"
            data-turbo="true"
            @class([
              'shrink-0 rounded-md px-2.5 py-1.25 text-sm transition-colors duration-(--duration-fast)',
              'bg-canvas font-medium text-ink' => request()->routeIs($item['route']),
              'text-on-primary/70 hover:bg-on-primary/10 hover:text-on-primary' => ! request()->routeIs($item['route']),
            ])
          >
            {{ $item['label'] }}
          </a>
        @endforeach
      </nav>
    </header>

    <main class="mx-auto flex w-full max-w-280 flex-col gap-8 px-4 pt-8 pb-14 sm:px-6">
      {{ $slot }}
    </main>

    <x-toaster />
  </body>
</html>
