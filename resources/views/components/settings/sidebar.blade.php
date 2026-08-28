{{--
  The column beside the settings screens: the way back out of them, what you can
  change about your own account, and who you are signed in as.

  Settings are somewhere you go and then leave, so the way out sits above the
  navigation rather than in it, the same way the vault switcher does in the
  other column.

  An entry under the pointer takes on the raised card the current one wears,
  minus its weight: the weight is what says where you are, and putting it on
  hover would shift the words sideways under the pointer. `aria-current` says the
  same thing to somebody who is listening rather than looking.

  Below lg the column has nowhere to go, so it becomes a drawer that slides in
  over the screen, opened by the button in the bar.

  The closed state is a plain class rather than a binding, since alpine only
  starts once the page has been painted and a bound one would let the drawer
  flash open on every load.

  @var \App\Models\User $user
  @var string $current
--}}
@props([
  'user',
  'current' => 'profile',
])

@php
  $item = 'flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm transition-colors duration-(--duration-fast) ease-out max-lg:py-2.5';

  $groups = [
    [
      'title' => __('You'),
      'entries' => [
        ['key' => 'profile', 'label' => __('Profile'), 'icon' => 'profile', 'url' => route('settings.profile.index')],
        ['key' => 'security', 'label' => __('Security and access'), 'icon' => 'lock', 'url' => route('settings.security.index')],
        ['key' => 'preferences', 'label' => __('Preferences'), 'icon' => 'settings', 'url' => route('settings.preferences.index')],
      ],
    ],
    [
      'title' => __('This account'),
      'entries' => [
        ['key' => 'templates', 'label' => __('Templates'), 'icon' => 'file', 'url' => route('settings.templates.index')],
        ['key' => 'entity-types', 'label' => __('Entity types'), 'icon' => 'organization', 'url' => route('settings.entityTypes.index')],
        ['key' => 'value-lists', 'label' => __('Default values'), 'icon' => 'tasks', 'url' => route('settings.valueLists.index')],
        ['key' => 'logs', 'label' => __('Logs'), 'icon' => 'timeline', 'url' => route('settings.logs.index')],
      ],
    ],
  ];
@endphp

<aside
  id="main-nav"
  :class="navOpen && 'max-lg:translate-x-0!'"
  class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-shell-hairline bg-shell transition-transform duration-(--duration-slow) ease-out max-lg:-translate-x-full lg:sticky lg:top-0 lg:h-screen lg:w-auto lg:translate-x-0"
>
  <div class="flex items-center gap-2.5 px-4 pt-3.5 pb-2.5">
    <x-logo :size="26" />

    <span class="truncate text-sm font-semibold tracking-tight text-shell-ink">{{ config('app.name') }}</span>

    <x-theme-toggle tone="shell" class="ml-auto" />

    <button
      type="button"
      @click="navOpen = false"
      aria-label="{{ __('Close the menu') }}"
      class="-mr-1 flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-shell-muted transition-colors duration-(--duration-fast) ease-out hover:bg-shell-hover hover:text-shell-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring lg:hidden"
    >
      <x-icon name="close" :size="15" :stroke-width="1.5" />
    </button>
  </div>

  <div class="mx-2 mb-1.5">
    <a
      href="{{ route('vaults.index') }}"
      data-turbo="true"
      class="flex items-center gap-2.5 rounded-md border border-shell-border bg-shell-raised px-2.5 py-2 text-[13px] text-shell-body transition-colors duration-(--duration-fast) ease-out hover:bg-shell-hover hover:text-shell-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
    >
      <x-icon name="arrow-right" :size="14" :stroke-width="1.5" class="shrink-0 rotate-180 text-shell-muted" />
      {{ __('Leave the settings') }}
    </a>
  </div>

  <nav aria-label="{{ __('Settings') }}" class="min-h-0 flex-1 space-y-px overflow-y-auto px-2 pt-1.5 pb-3">
    @foreach ($groups as $group)
      <h2 class="px-2.5 pt-1.5 pb-1.25 text-xs tracking-caption text-shell-muted uppercase">{{ $group['title'] }}</h2>

      @foreach ($group['entries'] as $entry)
        <a
          href="{{ $entry['url'] }}"
          data-turbo="true"
          @if ($current === $entry['key']) aria-current="page" @endif
          class="{{ $item }} {{ $current === $entry['key'] ? 'bg-shell-active font-semibold text-shell-active-ink' : 'text-shell-body hover:bg-shell-hover hover:text-shell-ink' }} focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
        >
          <x-icon :name="$entry['icon']" :size="16" :stroke-width="1.5" />
          {{ $entry['label'] }}
        </a>
      @endforeach
    @endforeach
  </nav>

  <x-sidebar-foot :user="$user" :item="$item" />
</aside>
