{{-- The column beside the screens of one vault: what it holds. --}}
{{--
  @var \App\Models\User $user
  @var \App\Models\Vault $vault
  @var string $current
  @var int $reminderCount
  @var list<array{key: string, name: string, icon: string, colour: string, url: string}> $entityTypes
  @var array{url: string, usesCommandKey: bool, keys: string, label: string} $searchShortcut
--}}

@php
  $item = 'flex min-h-8 items-center gap-2.5 rounded-md px-2 py-1.5 text-nav transition duration-(--duration-fast) ease-out max-lg:min-h-10';

  $sections = [
    ['key' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'heart', 'url' => route('vaults.show', $vault), 'badge' => null],
    ['key' => 'contacts', 'label' => __('People'), 'icon' => 'people', 'url' => route('contacts.index', $vault), 'badge' => $vault->contact_count > 0 ? (string) $vault->contact_count : null],
    ['key' => 'groups', 'label' => __('Groups'), 'icon' => 'family', 'url' => route('groups.index', $vault), 'badge' => null],
    ['key' => 'reminders', 'label' => __('Reminders'), 'icon' => 'reminder', 'url' => route('reminders.index', $vault), 'badge' => $reminderCount > 0 ? (string) $reminderCount : null],
    ['key' => 'vault-settings', 'label' => __('Vault settings'), 'icon' => 'vault', 'url' => route('vaults.settings.index', $vault), 'badge' => null],
  ];

  if (! \App\Helpers\FlagState::isOn('groups', $user->account)) {
    $sections = array_values(array_filter($sections, fn (array $section): bool => $section['key'] !== 'groups'));
  }
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
      href="{{ $searchShortcut['url'] }}"
      data-turbo="true"
      x-data="searchShortcut({ usesCommandKey: @js($searchShortcut['usesCommandKey']), fieldId: 'query', url: @js($searchShortcut['url']) })"
      @keydown.window="open($event)"
      aria-keyshortcuts="{{ $searchShortcut['keys'] }}"
      @if ($current === 'search') aria-current="page" @endif
      class="flex h-8.5 items-center gap-2 rounded-md border border-hairline-strong bg-canvas pr-2 pl-2.5 text-sm text-muted no-underline shadow-panel transition-colors duration-(--duration-fast) ease-out hover:text-ink focus-ring aria-[current=page]:text-ink"
    >
      <x-icon name="search" :size="15" :stroke-width="1.5" />
      <span class="flex-1">{{ __('Search') }}</span>
      <kbd aria-hidden="true" class="rounded-sm border border-hairline bg-card px-1.25 py-px font-sans text-2xs font-medium text-muted">{{ $searchShortcut['label'] }}</kbd>
    </a>
  </div>

  <nav aria-label="{{ __('This vault') }}" class="min-h-0 flex-1 space-y-px overflow-y-auto px-2 pt-1.5 pb-3">
    <h2 class="px-2 pt-4 pb-2.5 text-2xs font-medium tracking-caption text-muted-soft uppercase first:pt-1">{{ __('This vault') }}</h2>

    @foreach ($sections as $section)
      @if ($section['url'])
        <a
          href="{{ $section['url'] }}"
          data-turbo="true"
          @if ($current === $section['key']) aria-current="page" @endif
          class="{{ $item }} {{ $current === $section['key'] ? 'bg-canvas font-medium text-ink shadow-nav-active' : 'text-body hover:bg-hover hover:text-ink' }} focus-ring"
        >
          <x-icon :name="$section['icon']" :size="16" :stroke-width="1.5" />
          {{ $section['label'] }}

          @if ($section['badge'])
            <span class="ml-auto text-xs text-muted">{{ $section['badge'] }}</span>
          @endif
        </a>
      @else
        <span
          aria-disabled="true"
          @if ($current === $section['key']) aria-current="page" @endif
          class="{{ $item }} {{ $current === $section['key'] ? 'bg-canvas font-medium text-ink shadow-nav-active' : 'text-muted' }}"
        >
          <x-icon :name="$section['icon']" :size="16" :stroke-width="1.5" />
          {{ $section['label'] }}

          @if ($section['badge'])
            <span class="ml-auto text-xs text-muted">{{ $section['badge'] }}</span>
          @endif
        </span>
      @endif

      @if ($section['key'] === 'contacts')
        @foreach ($entityTypes as $type)
          <a
            href="{{ $type['url'] }}"
            data-turbo="true"
            @if ($current === $type['key']) aria-current="page" @endif
            class="{{ $item }} {{ $current === $type['key'] ? 'bg-canvas font-medium text-ink shadow-nav-active' : 'text-body hover:bg-hover hover:text-ink' }} focus-ring"
          >
            <span class="flex shrink-0" style="color: {{ $type['colour'] }}">
              <x-entity-icon :name="$type['icon']" :size="16" />
            </span>

            <span class="min-w-0 truncate">{{ $type['name'] }}</span>
          </a>
        @endforeach
      @endif
    @endforeach
  </nav>

  <x-sidebar-foot :user="$user" :item="$item" />
</aside>
