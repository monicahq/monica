{{--
  The column beside the settings screens: which company you are in, what you can
  change about your own account, and who you are signed in as.

  Below lg the column has nowhere to go, so it becomes a drawer that slides in
  over the screen, opened by the button in the bar. It is still the same aside
  rather than a second copy, because `sidebar-identity` below is the target of
  an ajax form on the profile screen and there can only be one of it.

  The closed state is a plain class rather than a binding, since alpine only
  starts once the page has been painted and a bound one would let the drawer
  flash open on every load. The open class is marked important because it and
  the closed one both write the same custom property, and which of the two won
  would otherwise come down to the order tailwind happened to emit them in.

  @var string $companyName
  @var string $name
  @var \App\Models\Employee|null $employee
  @var string $current
--}}
@props([
  'companyName',
  'name',
  'employee' => null,
  'current' => 'profile',
])

@php
  $item = 'flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm max-lg:py-2.5';
@endphp

<aside
  id="settings-nav"
  :class="navOpen && 'max-lg:translate-x-0!'"
  class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-hairline bg-sunken transition-transform duration-200 ease-out max-lg:-translate-x-full lg:sticky lg:top-0 lg:h-screen lg:w-auto lg:translate-x-0"
>
  <div class="flex items-center gap-2.5 px-4 pt-3.5 pb-2.5">
    <x-avatar-initials :name="$companyName" :size="26" />

    <span class="truncate text-sm font-semibold tracking-tight text-ink">{{ $companyName }}</span>

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

  <nav class="min-h-0 flex-1 space-y-px overflow-y-auto px-2 pt-2 pb-3">
    <h2 class="px-2.5 pt-1.5 pb-1.25 text-xs tracking-widest text-muted-soft uppercase">{{ __('Your account') }}</h2>

    <a
      href="{{ route('settings.profile.index') }}"
      data-turbo="true"
      @if ($current === 'profile') aria-current="page" @endif
      class="{{ $item }} {{ $current === 'profile' ? 'bg-hover font-semibold text-ink' : 'text-body hover:bg-hover' }}"
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
      class="{{ $item }} {{ $current === 'logs' ? 'bg-hover font-semibold text-ink' : 'text-body hover:bg-hover' }}"
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
      class="{{ $item }} {{ $current === 'security' ? 'bg-hover font-semibold text-ink' : 'text-body hover:bg-hover' }}"
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
      class="{{ $item }} {{ $current === 'preferences' ? 'bg-hover font-semibold text-ink' : 'text-body hover:bg-hover' }}"
    >
      <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="shrink-0" aria-hidden="true">
        <rect x="2.4" y="3.2" width="11.2" height="9.6" rx="1.5"></rect>
        <line x1="2.4" y1="6.4" x2="13.6" y2="6.4"></line>
      </svg>
      {{ __('Preferences') }}
    </a>
  </nav>

  <div id="sidebar-identity" class="flex items-center gap-2 border-t border-hairline-soft px-3 py-2.5">
    <x-avatar :employee="$employee" :name="$name" :size="26" />

    <span class="truncate text-xs font-semibold text-ink">{{ $name }}</span>
  </div>
</aside>
