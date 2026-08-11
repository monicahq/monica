{{--
  The main navigation: a dock that floats at the foot of the window. There is no
  column down the left.

  It carries four destinations and nothing else. Four is the whole point. They are
  the places somebody goes every day, they keep their order forever, and each one is
  named rather than left as an icon to be learned. Everything rarer than those four
  is behind the vault chip on the left of the dock, which opens the menu: switching
  vault, the settings, signing out. That is why the chip is not a fifth destination
  but the way out of the four.

  Where you are is carried by `aria-current` and by the glyph taking a plate in its
  own colour, so it is never colour alone that says it. On a screen that belongs to
  the account rather than to a vault, nothing is current: you got there from the
  menu, and marking a dock item would claim otherwise.

  The journal, reminders, tasks and gifts are each their own screen and none of them
  exists yet, so those are drawn and not offered. A link that leads nowhere is worse
  than a word that does not move.

  This component asks the database for the vaults of the account, which no other
  component here does. The alternative was every screen's view model carrying the
  same two queries, since the dock is on all of them and none of them is about it.
  A screen inside a vault passes that vault, and a screen outside every vault (the
  settings) leaves it out and gets the account's default one, or the first it made,
  so the four destinations always lead somewhere. An account that has made no vault
  at all still gets the chip, so there is a way to the menu.

  `open` is declared here, on the common parent of the dock, the scrim and the menu,
  and that parent is inside <body>: alpine is re-initialised from the body after a
  turbo navigation, so state declared above it would never be walked again.

  The keyboard shortcut is bound twice because the key that carries it is not the same
  one everywhere: it is cmd on a mac and ctrl on everything else, and Alpine matches
  the modifier rather than the intent.

  @var \App\Models\User $user
  @var \App\Models\Vault|null $vault
  @var string|null $current
--}}
@props([
  'user',
  'vault' => null,
  'current' => null,
])

@php
  $vaults = \App\Models\Vault::query()
    ->where('account_id', $user->account_id)
    ->orderByDesc('is_default')
    ->orderBy('position')
    ->get();

  $chipVault = $vault ?? $vaults->first();

  /*
   * The design calls the first destination "Home". That word is already taken: it
   * is the label an address or a phone number carries, and it is translated as
   * somebody's residence in every locale, so a dock that reused it would read
   * "Domicile" in French. The screen it opens is the one this codebase has always
   * called the dashboard, so that is what it is called here too.
   */
  $destinations = $chipVault === null ? [] : [
    ['key' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'heart', 'iconClass' => 'bg-terracotta-tint text-terracotta', 'url' => route('vaults.show', $chipVault)],
    ['key' => 'people', 'label' => __('People'), 'icon' => 'people', 'iconClass' => 'bg-indigo-tint text-indigo', 'url' => route('contacts.index', $chipVault)],
    ['key' => 'journal', 'label' => __('Journal'), 'icon' => 'journal', 'iconClass' => 'bg-plum-tint text-plum', 'url' => null],
    ['key' => 'reminders', 'label' => __('Reminders'), 'icon' => 'reminder', 'iconClass' => 'bg-forest-tint text-forest', 'url' => null],
  ];

  /* The vault the chip carries is not offered as somewhere else to go. */
  $elsewhere = $vaults
    ->reject(fn (\App\Models\Vault $option): bool => $chipVault !== null && $option->is($chipVault))
    ->map(fn (\App\Models\Vault $option): array => [
      'label' => $option->name,
      'icon' => 'vault',
      'iconClass' => 'bg-sky-tint text-sky',
      'note' => __('Another vault'),
      'url' => route('vaults.show', $option),
    ])
    ->values()
    ->all();

  $sections = [];

  if ($chipVault !== null) {
    $sections[] = [
      'title' => __('This vault'),
      'tiles' => [
        ['label' => __('People'), 'icon' => 'people', 'iconClass' => 'bg-indigo-tint text-indigo', 'note' => $chipVault->contact_count === 1 ? __('1 contact') : __(':count contacts', ['count' => $chipVault->contact_count]), 'url' => route('contacts.index', $chipVault)],
        ['label' => __('Journal'), 'icon' => 'journal', 'iconClass' => 'bg-plum-tint text-plum', 'note' => null, 'url' => null],
        ['label' => __('Reminders'), 'icon' => 'reminder', 'iconClass' => 'bg-forest-tint text-forest', 'note' => null, 'url' => null],
        ['label' => __('Tasks'), 'icon' => 'tasks', 'iconClass' => 'bg-marigold-tint text-marigold-deep', 'note' => null, 'url' => null],
        ['label' => __('Gifts'), 'icon' => 'gift', 'iconClass' => 'bg-rose-tint text-rose', 'note' => null, 'url' => null],
        ['label' => __('Vault settings'), 'icon' => 'vault', 'iconClass' => 'bg-terracotta-tint text-terracotta', 'note' => null, 'url' => route('vaults.settings.index', $chipVault)],
      ],
    ];
  }

  $sections[] = [
    'title' => __('Everything else'),
    'tiles' => [
      ...$elsewhere,
      ['label' => __('Your vaults'), 'icon' => 'vault', 'iconClass' => 'bg-sand text-muted', 'note' => null, 'url' => route('vaults.index')],
      ['label' => __('Settings'), 'icon' => 'settings', 'iconClass' => 'bg-forest-tint text-forest', 'note' => null, 'url' => route('settings.index')],
      ['label' => __('Sign out'), 'icon' => 'key', 'iconClass' => 'bg-sand text-muted', 'note' => null, 'url' => route('auth.login.destroy'), 'method' => 'post'],
    ],
  ];

  $brandLabel = $chipVault?->name ?? config('app.name');

  /*
   * Below 720px the dock stops floating in the middle and stretches between the
   * edges. The vault chip keeps its mark and drops its label; the destinations
   * share what is left.
   */
  $dock = 'fixed bottom-4.5 left-1/2 z-50 flex max-w-[calc(100vw-24px)] -translate-x-1/2 items-center gap-1 '
    .'rounded-xl border border-hairline-strong bg-canvas p-2 shadow-pop '
    .'max-[720px]:right-3 max-[720px]:bottom-3 max-[720px]:left-3 max-[720px]:translate-x-0 '
    .'max-[720px]:justify-between max-[720px]:rounded-lg';

  $item = 'relative flex h-15 min-w-[70px] flex-col items-center justify-center gap-1.25 rounded-lg px-2.5 '
    .'font-sans text-nav no-underline transition-[background-color,color,transform] duration-(--duration-fast) ease-out '
    .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring '
    .'max-[720px]:min-w-0 max-[720px]:flex-1';

  $glyph = 'flex h-6.5 w-9 items-center justify-center rounded-sm transition-[background-color,color] duration-(--duration-fast) ease-out';
@endphp

<div
  x-data="{ open: false }"
  @keydown.escape.window="open = false"
  @keydown.window.meta.k.prevent="open = ! open"
  @keydown.window.ctrl.k.prevent="open = ! open"
  @launcher-open.window="open = true"
>
  <div
    x-cloak
    x-show="open"
    x-transition:enter="transition-opacity ease-out duration-[170ms]"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition-opacity ease-out duration-[170ms]"
    x-transition:leave-end="opacity-0"
    @click="open = false"
    class="fixed inset-0 z-49 bg-scrim"
    aria-hidden="true"
  ></div>

  <x-launcher :sections="$sections" />

  <nav aria-label="{{ __('Main') }}" class="{{ $dock }}">
    <button
      type="button"
      @click="open = ! open"
      :aria-expanded="open ? 'true' : 'false'"
      aria-haspopup="menu"
      class="mr-0.5 flex h-15 cursor-pointer items-center justify-center gap-1.75 rounded-lg bg-action-strong px-4 font-sans text-nav text-canvas transition-[background-color,transform] duration-(--duration-fast) ease-out hover:bg-action-strong-hover active:translate-y-(--press-translate) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring max-[720px]:px-3"
    >
      {{-- The mark is ink on light and is never recoloured, so it keeps a light plate in both themes. --}}
      <span class="flex size-7 items-center justify-center rounded-sm bg-[#fbf8f3]">
        <x-logo :size="18" />
      </span>

      <span class="max-w-32 truncate max-[720px]:hidden">{{ $brandLabel }}</span>

      <x-icon
        name="chevron-down"
        :size="14"
        :stroke-width="1.8"
        class="opacity-60 transition-transform duration-[170ms]"
        ::class="open && 'rotate-180'"
      />
    </button>

    @if ($destinations !== [])
      <span class="mx-1 h-8 w-px bg-hairline" aria-hidden="true"></span>
    @endif

    {{--
      Only the ones that go somewhere behave like something you can press, and the
      glyph of the one you are on takes a plate in its own colour. A destination
      that has not been built yet is drawn and not offered: a link that leads
      nowhere is worse than a word that does not move.
    --}}
    @foreach ($destinations as $destination)
      @php
        $isCurrent = $current === $destination['key'];
      @endphp

      @if ($destination['url'])
        <a
          href="{{ $destination['url'] }}"
          data-turbo="true"
          @if ($isCurrent) aria-current="page" @endif
          class="{{ $item }} cursor-pointer hover:bg-sand hover:text-ink active:translate-y-(--press-translate) {{ $isCurrent ? 'text-ink' : 'text-muted' }}"
        >
          <span class="{{ $glyph }} {{ $isCurrent ? $destination['iconClass'] : '' }}">
            <x-icon :name="$destination['icon']" :size="23" :stroke-width="1.6" />
          </span>

          {{ $destination['label'] }}
        </a>
      @else
        <span class="{{ $item }} text-placeholder">
          <span class="{{ $glyph }}">
            <x-icon :name="$destination['icon']" :size="23" :stroke-width="1.6" />
          </span>

          {{ $destination['label'] }}
        </span>
      @endif
    @endforeach
  </nav>
</div>
