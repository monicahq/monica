{{-- The right side of the top bar: the places outside the vault, then what is yours. --}}
{{-- @var bool $settings --}}
@props([
  'settings' => true,
])

@php
  $places = array_filter([
    ['label' => __('Docs'), 'icon' => 'journal', 'href' => route('docs.index')],
    \App\Helpers\FlagState::isOn('marketplace', auth()->user()?->account)
      ? ['label' => __('Marketplace'), 'icon' => 'shop', 'href' => route('marketplace.index')]
      : null,
    $settings
      ? ['label' => __('Account settings'), 'icon' => 'settings', 'href' => route('settings.index')]
      : null,
  ]);
@endphp

<div class="flex shrink-0 items-center gap-0.5">
  <x-changelog-link />

  @foreach ($places as $place)
    <x-button.ghost :href="$place['href']" size="top-bar" :aria-label="$place['label']">
      <x-slot:icon>
        <x-icon :name="$place['icon']" :size="16" :stroke-width="1.4" />
      </x-slot:icon>

      <span class="hidden topbar-labels:inline">{{ $place['label'] }}</span>
    </x-button.ghost>
  @endforeach

  @if (auth()->user()?->isAdministrator())
    <x-button.secondary :href="route('admin.overview.index')" size="top-bar" :aria-label="__('Admin space')" class="ml-1.5">
      <x-slot:icon>
        <x-icon name="shield" :size="16" :stroke-width="1.4" />
      </x-slot:icon>

      <span class="hidden topbar-labels:inline">{{ __('Admin space') }}</span>
    </x-button.secondary>
  @endif

  <span aria-hidden="true" class="mx-1.5 h-5 w-px bg-hairline-strong max-sm:hidden"></span>

  <x-notification-bar />

  <x-theme-toggle />

  @auth
    <x-account-menu :user="auth()->user()" />
  @endauth
</div>
