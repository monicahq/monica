{{-- The shell of the admin space, which is the area for whoever runs the instance. --}}
{{--
  @var string|null $title
  @var \App\ViewModels\Admin\AdminNavigationViewModel $navigation
--}}
@php
  $supportToHandle = \App\Helpers\SupportAvailability::isEnabled() ? $navigation->supportToHandleCount() : 0;
  $changelogToReview = \App\Helpers\ChangelogAvailability::isEnabled() ? $navigation->changelogToReviewCount() : 0;
  $newAgentFeedback = $navigation->newAgentFeedbackCount();

  $groups = [
    [
      ['label' => __('Overview'), 'route' => 'admin.overview.index', 'section' => 'admin.overview.*', 'icon' => 'activity'],
    ],
    [
      ['label' => __('Marketplace review'), 'route' => 'admin.moderation.index', 'section' => 'admin.moderation.*', 'icon' => 'tasks'],
      ...(\App\Helpers\SupportAvailability::isEnabled() ? [['label' => __('Support requests'), 'route' => 'admin.support.index', 'section' => 'admin.support.*', 'icon' => 'mail', 'count' => $supportToHandle, 'countLabel' => trans_choice(':count request to handle|:count requests to handle', $supportToHandle)]] : []),
      ['label' => __('Agent reports'), 'route' => 'admin.agentFeedback.index', 'section' => 'admin.agentFeedback.*', 'icon' => 'bug', 'count' => $newAgentFeedback, 'countLabel' => trans_choice(':count new report|:count new reports', $newAgentFeedback)],
      ...(\App\Helpers\ChangelogAvailability::isEnabled() ? [['label' => __('Changelog feedback'), 'route' => 'admin.changelog.index', 'section' => 'admin.changelog.*', 'icon' => 'journal', 'count' => $changelogToReview, 'countLabel' => trans_choice(':count comment to review|:count comments to review', $changelogToReview)]] : []),
      ['label' => __('Waiting list'), 'route' => 'admin.previewList.index', 'section' => 'admin.previewList.*', 'icon' => 'clock'],
    ],
    [
      ['label' => __('Accounts'), 'route' => 'admin.accounts.index', 'section' => 'admin.accounts.*', 'icon' => 'people'],
      ['label' => __('Administrators'), 'route' => 'admin.administrators.index', 'section' => 'admin.administrators.*', 'icon' => 'key'],
    ],
    [
      ['label' => __('Instance settings'), 'route' => 'admin.configuration.index', 'section' => 'admin.configuration.*', 'icon' => 'settings'],
      ['label' => __('Search engine'), 'route' => 'admin.search.index', 'section' => 'admin.search.*', 'icon' => 'search'],
      ['label' => __('Billing'), 'route' => 'admin.billing.index', 'section' => 'admin.billing.*', 'icon' => 'credit-card'],
      ['label' => __('Feature flags'), 'route' => 'admin.flags.index', 'section' => 'admin.flags.*', 'icon' => 'tag'],
      ['label' => __('Administrator log'), 'route' => 'admin.logs.index', 'section' => 'admin.logs.*', 'icon' => 'timeline'],
    ],
  ];
  $groupTitles = [null, __('To handle'), __('People'), __('Instance')];

  $current = '';
  $navigationGroups = [];

  foreach ($groups as $index => $sections) {
    $entries = [];

    foreach ($sections as $section) {
      if (request()->routeIs($section['section'])) {
        $current = $section['route'];
      }

      $entries[] = [
        'key' => $section['route'],
        'label' => $section['label'],
        'icon' => $section['icon'],
        'url' => route($section['route']),
        'currentIsDescendant' => ! request()->routeIs($section['route']),
        'count' => $section['count'] ?? 0,
        'countLabel' => $section['countLabel'] ?? '',
      ];
    }

    $navigationGroups[] = $groupTitles[$index] === null
      ? ['entries' => $entries]
      : ['title' => $groupTitles[$index], 'entries' => $entries];
  }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Helpers\AvailableLocales::direction(app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div x-data="{ navOpen: false }">
      <header class="print:hidden bg-admin-bar text-on-admin-bar">
        <div aria-hidden="true" class="h-2 bg-[repeating-linear-gradient(-45deg,var(--admin-signal)_0_10px,var(--admin-bar)_10px_20px)]"></div>

        <div class="flex h-16 items-center gap-3 px-4 sm:gap-4 sm:px-5">
          <x-menu-button
            tone="inverted"
            :description="$supportToHandle + $changelogToReview + $newAgentFeedback > 0 ? __('Something is waiting in the admin sections') : null"
          />

          <div class="flex min-w-0 items-center gap-3">
            <x-logo :size="29" />

            <span class="truncate text-base font-semibold tracking-[-0.01em]">{{ __('Admin space') }}</span>

            <x-badge tone="signal" class="shrink-0 tracking-caption uppercase max-sm:hidden">{{ __('Instance') }}</x-badge>
          </div>

          <div class="ms-auto flex items-center gap-2">
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
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-[248px_minmax(0,1fr)] lg:items-start print:block">
        <div class="lg:sticky lg:top-0 lg:h-dvh">
          <x-nav-drawer
            :groups="$navigationGroups"
            :current="$current"
            :label="__('Admin sections')"
          />
        </div>

        <x-nav-scrim />

        <div class="min-w-0">
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
        </div>
      </div>
    </div>

    <x-toaster />
  </body>
</html>
