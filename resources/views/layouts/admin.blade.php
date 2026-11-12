{{-- The shell of the admin space, which is the area for whoever runs the instance. --}}
{{--
  @var string|null $title
  @var \App\ViewModels\Admin\AdminNavigationViewModel $navigation
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-pt-32">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <header class="sticky print:hidden top-0 z-5 bg-admin-bar text-on-admin-bar">
      <div aria-hidden="true" class="h-2 bg-[repeating-linear-gradient(-45deg,var(--admin-signal)_0_10px,var(--admin-bar)_10px_20px)]"></div>

      <div class="mx-auto max-w-280 px-4 sm:px-6">
        <div class="flex h-16 items-center gap-4">
          <div class="flex min-w-0 items-center gap-3">
            <x-logo :size="29" />

            <span class="truncate text-base font-semibold tracking-[-0.01em]">{{ __('Admin space') }}</span>

            <x-badge tone="signal" class="shrink-0 tracking-caption uppercase max-sm:hidden">{{ __('Instance') }}</x-badge>
          </div>

          <div class="ml-auto flex items-center gap-2">
            <a
              href="{{ route('home.index') }}"
              data-turbo="true"
              aria-label="{{ __('Leave the admin space') }}"
              class="inline-flex h-8.5 shrink-0 items-center gap-2 rounded-md border border-on-admin-bar/20 px-2 text-sm font-medium whitespace-nowrap text-on-admin-bar no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-on-admin-bar/10 focus-ring-on-admin-bar sm:px-3"
            >
              <x-icon name="chevron-right" :size="16" :stroke-width="1.4" class="rotate-180" />
              <span class="max-sm:hidden">{{ __('Leave the admin space') }}</span>
            </a>

            <x-theme-toggle tone="inverted" />

            <x-account-menu :user="auth()->user()" variant="inverted" />
          </div>
        </div>

        <nav aria-label="{{ __('Admin sections') }}" class="flex gap-1 overflow-x-auto">
          @foreach ([
            ['label' => __('Overview'), 'route' => 'admin.overview.index', 'section' => 'admin.overview.*', 'icon' => 'activity'],
            ['label' => __('Moderation'), 'route' => 'admin.moderation.index', 'section' => 'admin.moderation.*', 'icon' => 'tasks'],
            ...(\App\Helpers\SupportAvailability::isEnabled() ? [['label' => __('Support'), 'route' => 'admin.support.index', 'section' => 'admin.support.*', 'icon' => 'mail', 'count' => $navigation->supportToHandleCount()]] : []),
            ['label' => __('Accounts'), 'route' => 'admin.accounts.index', 'section' => 'admin.accounts.*', 'icon' => 'people'],
            ['label' => __('Configuration'), 'route' => 'admin.configuration.index', 'section' => 'admin.configuration.*', 'icon' => 'settings'],
            ['label' => __('Flags'), 'route' => 'admin.flags.index', 'section' => 'admin.flags.*', 'icon' => 'tag'],
            ['label' => __('Administrators'), 'route' => 'admin.administrators.index', 'section' => 'admin.administrators.*', 'icon' => 'key'],
            ['label' => __('Record'), 'route' => 'admin.logs.index', 'section' => 'admin.logs.*', 'icon' => 'timeline'],
          ] as $item)
            <a
              href="{{ route($item['route']) }}"
              data-turbo="true"
              @if (request()->routeIs($item['route']))
                aria-current="page"
              @elseif (request()->routeIs($item['section']))
                aria-current="true"
              @endif
              @class([
                'flex shrink-0 items-center gap-2 border-b-3 px-3 pt-2.5 pb-3 text-sm font-medium no-underline transition-colors duration-(--duration-fast) focus-ring-on-admin-bar-inset',
                'border-admin-signal text-on-admin-bar' => request()->routeIs($item['section']),
                'border-transparent text-on-admin-bar-muted hover:text-on-admin-bar' => ! request()->routeIs($item['section']),
              ])
            >
              <x-icon :name="$item['icon']" :size="16" class="max-sm:hidden" />
              {{ $item['label'] }}

              @if (($item['count'] ?? 0) > 0)
                <span class="flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-admin-signal px-1.25 text-2xs font-semibold text-admin-bar" aria-hidden="true">{{ $item['count'] }}</span>
                <span class="sr-only">{{ trans_choice(':count request to handle|:count requests to handle', $item['count']) }}</span>
              @endif
            </a>
          @endforeach
        </nav>
      </div>
    </header>

    <aside aria-labelledby="admin-space-warning" class="print:hidden border-b border-warning-edge bg-warning-tint">
      <div class="mx-auto flex max-w-280 items-center gap-3 px-4 py-3 text-ink-soft sm:px-6">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-md bg-admin-signal text-admin-bar">
          <x-icon name="warning" :size="16" :stroke-width="1.5" />
        </span>

        <p class="text-pretty"><strong id="admin-space-warning" class="font-semibold text-ink">{{ __('You are looking at the instance, not at your own account.') }}</strong> {{ __('Anything you change here applies to every account on it.') }}</p>
      </div>
    </aside>

    <main class="mx-auto flex w-full max-w-280 flex-col gap-8 px-4 pt-10 pb-16 sm:px-6">
      {{ $slot }}
    </main>

    <x-toaster />
  </body>
</html>
