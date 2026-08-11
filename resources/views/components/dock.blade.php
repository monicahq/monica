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
    ['key' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'heart', 'hue' => 'var(--terracotta)', 'tint' => 'var(--terracotta-tint)', 'url' => route('vaults.show', $chipVault)],
    ['key' => 'people', 'label' => __('People'), 'icon' => 'people', 'hue' => 'var(--indigo)', 'tint' => 'var(--indigo-tint)', 'url' => route('contacts.index', $chipVault)],
    ['key' => 'journal', 'label' => __('Journal'), 'icon' => 'journal', 'hue' => 'var(--plum)', 'tint' => 'var(--plum-tint)', 'url' => null],
    ['key' => 'reminders', 'label' => __('Reminders'), 'icon' => 'reminder', 'hue' => 'var(--forest)', 'tint' => 'var(--forest-tint)', 'url' => null],
  ];

  /* The vault the chip carries is not offered as somewhere else to go. */
  $elsewhere = $vaults
    ->reject(fn (\App\Models\Vault $option): bool => $chipVault !== null && $option->is($chipVault))
    ->map(fn (\App\Models\Vault $option): array => [
      'label' => $option->name,
      'icon' => 'vault',
      'hue' => 'var(--sky)',
      'tint' => 'var(--sky-tint)',
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
        ['label' => __('People'), 'icon' => 'people', 'hue' => 'var(--indigo)', 'tint' => 'var(--indigo-tint)', 'note' => $chipVault->contact_count === 1 ? __('1 contact') : __(':count contacts', ['count' => $chipVault->contact_count]), 'url' => route('contacts.index', $chipVault)],
        ['label' => __('Journal'), 'icon' => 'journal', 'hue' => 'var(--plum)', 'tint' => 'var(--plum-tint)', 'note' => null, 'url' => null],
        ['label' => __('Reminders'), 'icon' => 'reminder', 'hue' => 'var(--forest)', 'tint' => 'var(--forest-tint)', 'note' => null, 'url' => null],
        ['label' => __('Tasks'), 'icon' => 'tasks', 'hue' => 'var(--marigold-deep)', 'tint' => 'var(--marigold-tint)', 'note' => null, 'url' => null],
        ['label' => __('Gifts'), 'icon' => 'gift', 'hue' => 'var(--rose)', 'tint' => 'var(--rose-tint)', 'note' => null, 'url' => null],
        ['label' => __('Vault settings'), 'icon' => 'vault', 'hue' => 'var(--terracotta)', 'tint' => 'var(--terracotta-tint)', 'note' => null, 'url' => route('vaults.settings.index', $chipVault)],
      ],
    ];
  }

  $sections[] = [
    'title' => __('Everything else'),
    'tiles' => [
      ...$elsewhere,
      ['label' => __('Your vaults'), 'icon' => 'vault', 'hue' => 'var(--muted)', 'tint' => 'var(--sand)', 'note' => null, 'url' => route('vaults.index')],
      ['label' => __('Settings'), 'icon' => 'settings', 'hue' => 'var(--forest)', 'tint' => 'var(--forest-tint)', 'note' => null, 'url' => route('settings.index')],
      ['label' => __('Sign out'), 'icon' => 'key', 'hue' => 'var(--muted)', 'tint' => 'var(--sand)', 'note' => null, 'url' => route('auth.login.destroy'), 'method' => 'post'],
    ],
  ];

  $brandLabel = $chipVault?->name ?? config('app.name');
@endphp

<div
  x-data="{ open: false }"
  @keydown.escape.window="open = false"
  @keydown.window.meta.k.prevent="open = ! open"
  @keydown.window.ctrl.k.prevent="open = ! open"
>
  <div
    x-cloak
    x-show="open"
    x-transition:enter="transition-opacity ease-out duration-[170ms]"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition-opacity ease-out duration-[170ms]"
    x-transition:leave-end="opacity-0"
    @click="open = false"
    class="erica-scrim erica-launcher__scrim"
    aria-hidden="true"
  ></div>

  <x-launcher :sections="$sections" />

  <nav aria-label="{{ __('Main') }}" class="erica-dock">
    <button
      type="button"
      @click="open = ! open"
      :aria-expanded="open ? 'true' : 'false'"
      aria-haspopup="menu"
      class="erica-dock__brand"
    >
      <span class="erica-dock__mark">
        <x-logo :size="18" />
      </span>

      <span class="max-w-32 truncate">{{ $brandLabel }}</span>

      <x-icon
        name="chevron-down"
        :size="14"
        :stroke-width="1.8"
        class="opacity-60 transition-transform duration-[170ms]"
        ::class="open && 'rotate-180'"
      />
    </button>

    @if ($destinations !== [])
      <span class="erica-dock__rule" aria-hidden="true"></span>
    @endif

    @foreach ($destinations as $destination)
      @if ($destination['url'])
        <a
          href="{{ $destination['url'] }}"
          data-turbo="true"
          @if ($current === $destination['key']) aria-current="page" @endif
          class="erica-dock__item"
          style="--hue: {{ $destination['hue'] }}; --tint: {{ $destination['tint'] }}"
        >
          <span class="erica-dock__glyph">
            <x-icon :name="$destination['icon']" :size="23" :stroke-width="1.6" />
          </span>

          {{ $destination['label'] }}
        </a>
      @else
        <span class="erica-dock__item erica-dock__item--unbuilt">
          <span class="erica-dock__glyph">
            <x-icon :name="$destination['icon']" :size="23" :stroke-width="1.6" />
          </span>

          {{ $destination['label'] }}
        </span>
      @endif
    @endforeach
  </nav>
</div>
