{{-- The column beside the settings screens, and the way back out of them. --}}
{{--
  @var \App\Models\User $user
  @var string $current
--}}
@props([
  'user',
  'current' => 'profile',
])

@php
  $item = 'flex min-h-8 items-center gap-2.5 rounded-md px-2 py-1.5 text-nav transition duration-(--duration-fast) ease-out max-lg:min-h-10';

  $groups = [
    [
      'title' => __('You'),
      'entries' => [
        ['key' => 'profile', 'label' => __('Profile'), 'icon' => 'profile', 'url' => route('settings.profile.index')],
        ['key' => 'security', 'label' => __('Security and access'), 'icon' => 'lock', 'url' => route('settings.security.index')],
        ['key' => 'preferences', 'label' => __('Preferences'), 'icon' => 'settings', 'url' => route('settings.preferences.index')],
        ['key' => 'notifications', 'label' => __('Notifications'), 'icon' => 'bell', 'url' => route('settings.notifications.index')],
        ['key' => 'logs', 'label' => __('Logs'), 'icon' => 'timeline', 'url' => route('settings.logs.index')],
        ['key' => 'transfer', 'label' => __('Export and import'), 'icon' => 'file', 'url' => route('settings.accountImports.index')],
      ],
    ],
    [
      'title' => __('Personalisation'),
      'entries' => [
        ['key' => 'entity-types', 'label' => __('Contacts and more'), 'icon' => 'organization', 'url' => route('settings.entityTypes.index')],
        ['key' => 'templates', 'label' => __('Templates'), 'icon' => 'file', 'url' => route('settings.templates.index')],
        ['key' => 'value-lists', 'label' => __('Value lists'), 'icon' => 'tasks', 'url' => route('settings.valueLists.index')],
        ['key' => 'relationship-types', 'label' => __('Relationship types'), 'icon' => 'relationship', 'url' => route('settings.relationshipTypes.index')],
        ['key' => 'life-event-types', 'label' => __('Life event types'), 'icon' => 'activity', 'url' => route('settings.lifeEventTypes.index')],
      ],
    ],
    ...(config('monica.self_hosted') ? [] : [[
      'title' => __('Account'),
      'entries' => [
        ['key' => 'billing', 'label' => __('Billing'), 'icon' => 'coin', 'url' => route('settings.billing.index')],
      ],
    ]]),
  ];
@endphp

<aside
  id="main-nav"
  :class="navOpen && 'max-lg:translate-x-0!'"
  class="fixed print:hidden inset-y-0 left-0 z-40 flex w-62 flex-col border-r border-hairline bg-card transition-transform duration-(--duration-slow) ease-out max-lg:-translate-x-full lg:static lg:h-full lg:w-auto lg:translate-x-0"
>
  <div class="flex items-center gap-2.5 px-4 pt-3.5 pb-2.5">
    <a
      href="{{ route('vaults.index') }}"
      data-turbo="true"
      class="flex min-w-0 items-center gap-2.5 rounded-md no-underline focus-ring"
    >
      <x-logo :size="26" />

      <span class="truncate text-sm font-semibold tracking-tight text-ink">{{ config('app.name') }}</span>
    </a>

    <x-theme-toggle class="ml-auto" />

    <button
      type="button"
      @click="navOpen = false"
      aria-label="{{ __('Close the menu') }}"
      class="-mr-1 flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring lg:hidden"
    >
      <x-icon name="close" :size="15" :stroke-width="1.5" />
    </button>
  </div>

  <div class="mx-2 mb-1.5">
    <a
      href="{{ route('vaults.index') }}"
      data-turbo="true"
      class="flex items-center gap-2.5 rounded-md border border-hairline bg-canvas px-2.5 py-2 text-sm text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
    >
      <x-icon name="arrow-right" :size="14" :stroke-width="1.5" class="shrink-0 rotate-180 text-muted" />
      {{ __('Leave the settings') }}
    </a>
  </div>

  <nav aria-label="{{ __('Settings') }}" class="min-h-0 flex-1 space-y-px overflow-y-auto px-2 pt-1.5 pb-3">
    @foreach ($groups as $group)
      <h2 class="px-2 pt-4 pb-2.5 text-2xs font-medium tracking-caption text-muted-soft uppercase first:pt-1">{{ $group['title'] }}</h2>

      @foreach ($group['entries'] as $entry)
        <a
          href="{{ $entry['url'] }}"
          data-turbo="true"
          @if ($current === $entry['key']) aria-current="page" @endif
          class="{{ $item }} {{ $current === $entry['key'] ? 'bg-canvas font-medium text-ink shadow-nav-active' : 'text-body hover:bg-hover hover:text-ink' }} focus-ring"
        >
          <x-icon :name="$entry['icon']" :size="16" :stroke-width="1.5" />
          {{ $entry['label'] }}
        </a>
      @endforeach
    @endforeach
  </nav>

  <x-sidebar-foot :user="$user" :item="$item" />
</aside>
