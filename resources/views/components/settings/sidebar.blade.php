{{--
  The column beside the settings screens: the way back out of them, what you can
  change about your own account, and who you are signed in as.

  Settings are somewhere you go and then leave, so the way out sits above the
  navigation rather than in it, the same way the vault switcher does in the
  other sidebar.

  An entry under the pointer takes on the raised card the current one wears,
  minus its weight: the weight is what says where you are, and putting it on
  hover would shift the words sideways under the pointer.

  Below lg the column has nowhere to go, so it becomes a drawer that slides in
  over the screen, opened by the button in the bar. It is still the same aside
  rather than a second copy, because `sidebar-identity` below is the target of
  an ajax form on the profile screen and there can only be one of it.

  The closed state is a plain class rather than a binding, since alpine only
  starts once the page has been painted and a bound one would let the drawer
  flash open on every load. The open class is marked important because it and
  the closed one both write the same custom property, and which of the two won
  would otherwise come down to the order tailwind happened to emit them in.

  @var \App\Models\User $user
  @var string $current
--}}
@props([
  'user',
  'current' => 'profile',
])

@php
  $item = 'flex items-center gap-2.5 rounded-md border px-2.5 py-1.5 text-sm transition-colors max-lg:py-2.5';
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

  <a
    href="{{ route('vaults.index') }}"
    data-turbo="true"
    class="mx-2 mb-1.5 flex items-center gap-2.5 rounded-md border border-hairline-strong bg-canvas px-2.5 py-2 text-sm text-body transition-colors hover:bg-hover hover:text-ink"
  >
    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true">
      <line x1="13" y1="8" x2="3.4" y2="8"></line>
      <path d="M7 4.4 3.4 8 7 11.6"></path>
    </svg>
    {{ __('Back to your vaults') }}
  </a>

  <nav class="min-h-0 flex-1 space-y-px overflow-y-auto px-2 pt-1.5 pb-3">
    <h2 class="px-2.5 pt-1.5 pb-1.25 text-xs text-muted-soft uppercase">{{ __('Your account') }}</h2>

    <a
      href="{{ route('settings.profile.index') }}"
      data-turbo="true"
      @if ($current === 'profile') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'profile' ? 'border-hairline-strong bg-canvas font-semibold text-ink' : 'border-transparent text-body hover:border-hairline-strong hover:bg-canvas hover:text-ink' }}"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0" aria-hidden="true">
        <circle cx="8" cy="6" r="2.6"></circle>
        <path d="M3 14c0-2.6 2.2-4 5-4s5 1.4 5 4"></path>
      </svg>
      {{ __('Profile') }}
    </a>

    <a
      href="{{ route('settings.logs.index') }}"
      data-turbo="true"
      @if ($current === 'logs') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'logs' ? 'border-hairline-strong bg-canvas font-semibold text-ink' : 'border-transparent text-body hover:border-hairline-strong hover:bg-canvas hover:text-ink' }}"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0" aria-hidden="true">
        <path d="M4 2.4h5.2L12.4 5.6V13.6H4z"></path>
        <line x1="6.2" y1="8" x2="10.2" y2="8"></line>
        <line x1="6.2" y1="10.6" x2="10.2" y2="10.6"></line>
      </svg>
      {{ __('Logs') }}
    </a>

    <a
      href="{{ route('settings.security.index') }}"
      data-turbo="true"
      @if ($current === 'security') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'security' ? 'border-hairline-strong bg-canvas font-semibold text-ink' : 'border-transparent text-body hover:border-hairline-strong hover:bg-canvas hover:text-ink' }}"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0" aria-hidden="true">
        <rect x="3.4" y="7" width="9.2" height="6.6" rx="1.5"></rect>
        <path d="M5.6 7V5.2a2.4 2.4 0 0 1 4.8 0V7"></path>
      </svg>
      {{ __('Security and access') }}
    </a>

    <a
      href="{{ route('settings.preferences.index') }}"
      data-turbo="true"
      @if ($current === 'preferences') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'preferences' ? 'border-hairline-strong bg-canvas font-semibold text-ink' : 'border-transparent text-body hover:border-hairline-strong hover:bg-canvas hover:text-ink' }}"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0" aria-hidden="true">
        <rect x="2.4" y="3.2" width="11.2" height="9.6" rx="1.5"></rect>
        <line x1="2.4" y1="6.4" x2="13.6" y2="6.4"></line>
      </svg>
      {{ __('Preferences') }}
    </a>

    <h2 class="px-2.5 pt-4.5 pb-1.25 text-xs text-muted-soft uppercase">{{ __('Personalization') }}</h2>

    <a
      href="{{ route('settings.valueLists.index') }}"
      data-turbo="true"
      @if ($current === 'defaults') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'defaults' ? 'border-hairline-strong bg-canvas font-semibold text-ink' : 'border-transparent text-body hover:border-hairline-strong hover:bg-canvas hover:text-ink' }}"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" class="shrink-0" aria-hidden="true">
        <line x1="4" y1="4.4" x2="13" y2="4.4"></line>
        <line x1="4" y1="8" x2="13" y2="8"></line>
        <line x1="4" y1="11.6" x2="13" y2="11.6"></line>
      </svg>
      {{ __('Default values') }}
    </a>

    <a
      href="{{ route('settings.entityTypes.index') }}"
      data-turbo="true"
      @if ($current === 'entities') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'entities' ? 'border-hairline-strong bg-canvas font-semibold text-ink' : 'border-transparent text-body hover:border-hairline-strong hover:bg-canvas hover:text-ink' }}"
    >
      <x-entity-icon name="device" />
      {{ __('Entity types') }}
    </a>

    <a
      href="{{ route('settings.templates.index') }}"
      data-turbo="true"
      @if ($current === 'templates') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'templates' ? 'border-hairline-strong bg-canvas font-semibold text-ink' : 'border-transparent text-body hover:border-hairline-strong hover:bg-canvas hover:text-ink' }}"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0" aria-hidden="true">
        <rect x="2.6" y="2.6" width="10.8" height="10.8" rx="1.6"></rect>
        <line x1="2.6" y1="6.2" x2="13.4" y2="6.2"></line>
        <line x1="6.2" y1="6.2" x2="6.2" y2="13.4"></line>
      </svg>
      {{ __('Templates') }}
    </a>
  </nav>

  <div id="sidebar-identity" class="flex items-center gap-2 border-t border-hairline-soft px-3 py-2.5">
    <x-avatar :user="$user" :size="26" />

    <span class="truncate text-xs font-semibold text-ink">{{ $user->name }}</span>
  </div>
</aside>
