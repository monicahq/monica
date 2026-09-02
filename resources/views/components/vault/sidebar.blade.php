{{--
  The column beside the screens of one vault: what the vault holds, and the way
  out of it into another one.

  The switcher at the top is the only way across the boundary, which is why it
  sits above the navigation rather than in it. It lists every vault of the
  account, and the one being looked at is marked rather than repeated as a link
  to where you already are.

  The navigation is about the vault and nothing else. What belongs to the
  account is reached from the row at the foot of the column, which is who you
  are signed in as and is the way into your own settings: the whole row is the
  link, and it lights up under the pointer like any entry above it.

  Some of the navigation points nowhere yet. The tasks, the reminders and the
  files are each their own screen and none of them exists, so those entries are
  drawn and not offered: a link that leads nowhere is worse than a word that does
  not move.

  This component asks the account for its vaults itself, rather than being handed
  them. It is on every screen inside a vault and none of those screens is about
  it, so the alternative was the same two queries in thirty view models.

  Below lg the column becomes a drawer over the screen, opened from the bar,
  which is the layout's doing rather than this component's.

  @var \App\Models\User $user
  @var \App\Models\Vault $vault
  @var string $current
  @var int|null $taskCount
--}}
@props([
  'user',
  'vault',
  'current' => 'dashboard',
  'taskCount' => null,
])

@php
  $item = 'flex min-h-8 items-center gap-2.5 rounded-md px-2 py-1.5 text-nav transition-[background-color,box-shadow,color] duration-(--duration-fast) ease-out max-lg:min-h-10';

  $vaults = \App\Models\Vault::query()
    ->where('account_id', $user->account_id)
    ->orderByDesc('is_default')
    ->orderBy('position')
    ->get();

  $sections = [
    ['key' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'heart', 'url' => route('vaults.show', $vault), 'badge' => null],
    ['key' => 'contacts', 'label' => __('People'), 'icon' => 'people', 'url' => route('contacts.index', $vault), 'badge' => $vault->contact_count > 0 ? (string) $vault->contact_count : null],
    ['key' => 'tasks', 'label' => __('Tasks'), 'icon' => 'tasks', 'url' => null, 'badge' => $taskCount === null ? null : (string) $taskCount],
    ['key' => 'reminders', 'label' => __('Reminders'), 'icon' => 'reminder', 'url' => null, 'badge' => null],
    ['key' => 'files', 'label' => __('Files'), 'icon' => 'file', 'url' => null, 'badge' => null],
    ['key' => 'vault-settings', 'label' => __('Vault settings'), 'icon' => 'vault', 'url' => route('vaults.settings.index', $vault), 'badge' => null],
  ];
@endphp

<aside
  id="main-nav"
  :class="navOpen && 'max-lg:translate-x-0!'"
  class="fixed inset-y-0 left-0 z-40 flex w-62 flex-col border-r border-hairline bg-card transition-transform duration-(--duration-slow) ease-out max-lg:-translate-x-full lg:static lg:h-full lg:w-auto lg:translate-x-0"
>
  <div class="flex items-center gap-2.5 px-4 pt-3.5 pb-2.5">
    <x-logo :size="26" />

    <span class="truncate text-sm font-semibold tracking-tight text-ink">{{ config('app.name') }}</span>

    <x-theme-toggle class="ml-auto" />

    <button
      type="button"
      @click="navOpen = false"
      aria-label="{{ __('Close the menu') }}"
      class="-mr-1 flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none lg:hidden"
    >
      <x-icon name="close" :size="15" :stroke-width="1.5" />
    </button>
  </div>

  <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative mx-2 mb-1.5">
    <button
      type="button"
      @click="open = ! open"
      @click.outside="open = false"
      :aria-expanded="open ? 'true' : 'false'"
      class="flex w-full cursor-pointer items-center gap-2.5 rounded-md border border-hairline bg-canvas px-2.5 py-2 text-left transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
    >
      <span
        style="background: {{ $vault->colour }}"
        class="flex size-6 shrink-0 items-center justify-center rounded-sm text-xs font-semibold text-white"
        aria-hidden="true"
      >{{ mb_strtoupper(mb_substr($vault->name, 0, 1)) }}</span>

      <span class="min-w-0">
        <span class="block truncate text-[13px] font-semibold text-ink">{{ $vault->name }}</span>

        <span class="block text-[11px] text-muted">
          {{ trans_choice(':count person|:count people', $vault->contact_count) }}
        </span>
      </span>

      <x-icon name="chevron-down" :size="14" :stroke-width="1.5" class="ml-auto shrink-0 text-muted transition-transform duration-(--duration-standard)" ::class="open && 'rotate-180'" />
    </button>

    <div
      x-cloak
      x-show="open"
      x-transition.opacity.duration.120ms
      class="absolute inset-x-0 top-full z-10 mt-1 overflow-hidden rounded-lg border border-hairline bg-canvas shadow-pop"
    >
      @foreach ($vaults as $option)
        @if ($option->is($vault))
          <span
            aria-current="true"
            class="flex items-center gap-2.5 bg-hover px-2.5 py-2"
          >
            <span style="background: {{ $option->colour }}" class="size-2.5 shrink-0 rounded-sm" aria-hidden="true"></span>

            <span class="truncate text-[13px] font-semibold text-ink">{{ $option->name }}</span>

            <span class="ml-auto text-[11px] text-muted">{{ __('You are here') }}</span>
          </span>
        @else
          <a
            href="{{ route('vaults.show', $option) }}"
            data-turbo="true"
            class="flex items-center gap-2.5 px-2.5 py-2 transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-visible:inset-ring-2 focus-visible:inset-ring-focus-ring/45 focus-visible:outline-none"
          >
            <span style="background: {{ $option->colour }}" class="size-2.5 shrink-0 rounded-sm" aria-hidden="true"></span>

            <span class="truncate text-[13px] text-body">{{ $option->name }}</span>
          </a>
        @endif
      @endforeach

      <a href="{{ route('vaults.index') }}" data-turbo="true" class="block border-t border-hairline px-2.5 py-2 text-[13px] text-muted transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:inset-ring-2 focus-visible:inset-ring-focus-ring/45 focus-visible:outline-none">
        {{ __('All your vaults') }}
      </a>
    </div>
  </div>

  <nav aria-label="{{ __('This vault') }}" class="min-h-0 flex-1 space-y-px overflow-y-auto px-2 pt-1.5 pb-3">
    <h2 class="px-2 pt-1 pb-1.5 text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('This vault') }}</h2>

    @foreach ($sections as $section)
      @if ($section['url'])
        <a
          href="{{ $section['url'] }}"
          data-turbo="true"
          @if ($current === $section['key']) aria-current="page" @endif
          class="{{ $item }} {{ $current === $section['key'] ? 'bg-canvas font-medium text-ink shadow-nav-active' : 'text-body hover:bg-hover hover:text-ink' }} focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
        >
          <x-icon :name="$section['icon']" :size="16" :stroke-width="1.5" />
          {{ $section['label'] }}

          @if ($section['badge'])
            <span class="ml-auto text-xs text-muted">{{ $section['badge'] }}</span>
          @endif
        </a>
      @else
        {{--
          A section with no screen of its own is drawn quiet and does nothing.
          It can still be where you are: writing a contact down is a contacts
          screen even though the list of them is not built, and a nav that
          highlights nothing while you stand on it says you are nowhere.
        --}}
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
    @endforeach
  </nav>

  <x-sidebar-foot :user="$user" :item="$item" />
</aside>
