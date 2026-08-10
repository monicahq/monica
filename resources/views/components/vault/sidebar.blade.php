{{--
  The column beside the screens of one vault: what the vault holds, and the way
  out of it into another one or into the settings.

  The switcher at the top is the only way across the boundary, which is why it
  sits above the navigation rather than in it. It lists every vault of the
  account, and the one being looked at is marked rather than repeated as a link
  to where you already are.

  Most of the navigation points nowhere yet. Contacts, the journal, tasks,
  reminders, files and the settings of a vault are each their own screen and
  none of them exists, so those entries are drawn and not offered: a link that
  leads nowhere is worse than a word that does not move.

  Below lg the column becomes a drawer over the screen, opened from the bar,
  which is the layout's doing rather than this component's.

  @var \App\Models\Vault $vault
  @var array<int, array{name: string, colour: string, url: string, current: bool}> $vaults
  @var \App\Models\User $user
  @var string $current
  @var int|null $taskCount
--}}
@props([
  'vault',
  'vaults' => [],
  'user',
  'current' => 'dashboard',
  'taskCount' => null,
])

@php
  $item = 'flex items-center gap-2.5 rounded-md border px-2.5 py-1.5 text-sm transition-colors max-lg:py-2.5';

  $sections = [
    ['key' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'activity', 'url' => route('vaults.show', $vault), 'badge' => null],
    ['key' => 'contacts', 'label' => __('Contacts'), 'icon' => 'profile', 'url' => null, 'badge' => $vault->contact_count > 0 ? (string) $vault->contact_count : null],
    ['key' => 'journal', 'label' => __('Journal'), 'icon' => 'logs', 'url' => null, 'badge' => null],
    ['key' => 'tasks', 'label' => __('Tasks'), 'icon' => 'check', 'url' => null, 'badge' => $taskCount === null ? null : (string) $taskCount],
    ['key' => 'reminders', 'label' => __('Reminders'), 'icon' => 'mail', 'url' => null, 'badge' => null],
    ['key' => 'files', 'label' => __('Files'), 'icon' => 'device', 'url' => null, 'badge' => null],
    ['key' => 'vault-settings', 'label' => __('Vault settings'), 'icon' => 'security', 'url' => null, 'badge' => null],
  ];
@endphp

<aside
  id="settings-nav"
  :class="navOpen && 'max-lg:translate-x-0!'"
  class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-hairline bg-sunken transition-transform duration-200 ease-out max-lg:-translate-x-full lg:sticky lg:top-0 lg:h-screen lg:w-auto lg:translate-x-0"
>
  <div class="flex items-center gap-2.5 px-4 pt-3.5 pb-2.5">
    <x-logo :size="26" />

    <span class="truncate text-sm font-semibold tracking-tight text-ink">{{ config('app.name') }}</span>

    <x-theme-toggle class="ml-auto" />

    <button
      type="button"
      @click="navOpen = false"
      aria-label="{{ __('Close the menu') }}"
      class="-mr-1 flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted transition-colors hover:bg-hover hover:text-ink lg:hidden"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
        <line x1="4" y1="4" x2="12" y2="12"></line>
        <line x1="12" y1="4" x2="4" y2="12"></line>
      </svg>
    </button>
  </div>

  <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative mx-2 mb-1.5">
    <button
      type="button"
      @click="open = ! open"
      @click.outside="open = false"
      :aria-expanded="open ? 'true' : 'false'"
      class="flex w-full cursor-pointer items-center gap-2.5 rounded-md border border-hairline-strong bg-canvas px-2.5 py-2 text-left transition-colors hover:bg-hover"
    >
      <span
        style="background: {{ $vault->colour }}"
        class="flex size-6 shrink-0 items-center justify-center rounded-sm text-xs font-semibold text-white"
        aria-hidden="true"
      >{{ mb_strtoupper(mb_substr($vault->name, 0, 1)) }}</span>

      <span class="min-w-0">
        <span class="block truncate text-[13px] font-semibold text-ink">{{ $vault->name }}</span>

        <span class="block text-[11px] text-muted-soft">
          {{ $vault->contact_count === 1 ? __('1 contact') : __(':count contacts', ['count' => $vault->contact_count]) }}
        </span>
      </span>

      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" class="ml-auto shrink-0 text-muted-soft transition-transform duration-200" :class="open && 'rotate-180'" aria-hidden="true">
        <path d="M4.4 6.4 8 10l3.6-3.6"></path>
      </svg>
    </button>

    <div
      x-cloak
      x-show="open"
      x-transition.opacity.duration.120ms
      class="absolute inset-x-0 top-full z-10 mt-1 overflow-hidden rounded-lg border border-hairline-strong bg-canvas shadow-lg"
    >
      @foreach ($vaults as $option)
        <a
          href="{{ $option['url'] }}"
          data-turbo="true"
          @if ($option['current']) aria-current="page" @endif
          class="flex items-center gap-2.5 px-2.5 py-2 transition-colors hover:bg-hover {{ $option['current'] ? 'bg-card' : '' }}"
        >
          <span style="background: {{ $option['colour'] }}" class="size-2.5 shrink-0 rounded-sm" aria-hidden="true"></span>

          <span class="truncate text-[13px] text-ink {{ $option['current'] ? 'font-semibold' : '' }}">{{ $option['name'] }}</span>
        </a>
      @endforeach

      <a href="{{ route('vaults.index') }}" data-turbo="true" class="block border-t border-hairline-soft px-2.5 py-2 text-[13px] text-muted transition-colors hover:bg-hover hover:text-ink">
        {{ __('All your vaults') }}
      </a>
    </div>
  </div>

  <nav class="min-h-0 flex-1 space-y-px overflow-y-auto px-2 pt-1.5 pb-3">
    <h2 class="px-2.5 pt-1.5 pb-1.25 text-xs tracking-widest text-muted-soft uppercase">{{ __('This vault') }}</h2>

    @foreach ($sections as $section)
      @if ($section['url'])
        <a
          href="{{ $section['url'] }}"
          data-turbo="true"
          @if ($current === $section['key']) aria-current="page" @endif
          class="{{ $item }} {{ $current === $section['key'] ? 'border-hairline-strong bg-canvas font-semibold text-ink' : 'border-transparent text-body hover:border-hairline-strong hover:bg-canvas hover:text-ink' }}"
        >
          <x-entity-icon :name="$section['icon']" />
          {{ $section['label'] }}

          @if ($section['badge'])
            <span class="ml-auto text-xs text-muted-soft">{{ $section['badge'] }}</span>
          @endif
        </a>
      @else
        <span aria-disabled="true" class="{{ $item }} border-transparent text-muted-soft">
          <x-entity-icon :name="$section['icon']" />
          {{ $section['label'] }}

          @if ($section['badge'])
            <span class="ml-auto text-xs text-muted-soft">{{ $section['badge'] }}</span>
          @endif
        </span>
      @endif
    @endforeach

    <h2 class="px-2.5 pt-4.5 pb-1.25 text-xs tracking-widest text-muted-soft uppercase">{{ __('Your account') }}</h2>

    <a href="{{ route('settings.profile.index') }}" data-turbo="true" class="{{ $item }} border-transparent text-body hover:border-hairline-strong hover:bg-canvas hover:text-ink">
      <x-entity-icon name="preferences" />
      {{ __('Settings') }}
    </a>
  </nav>

  <div class="flex items-center gap-2 border-t border-hairline-soft px-3 py-2.5">
    <x-avatar :user="$user" :size="26" />

    <span class="truncate text-xs font-semibold text-ink">{{ $user->name }}</span>
  </div>
</aside>
