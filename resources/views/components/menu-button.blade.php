{{-- The button that opens the navigation drawer on a narrow window. --}}
{{--
  @var string $tone
  @var string|null $description
--}}
@props([
  'tone' => 'default',
  'description' => null,
])

@php
  $base = 'relative -ml-1.5 flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-md transition-colors duration-(--duration-fast) ease-out lg:hidden';

  $variant = match ($tone) {
    'inverted' => 'text-on-admin-bar-muted hover:bg-on-admin-bar/10 hover:text-on-admin-bar focus-ring-on-admin-bar',
    default => 'text-muted hover:bg-hover hover:text-ink focus-ring',
  };
@endphp

<button
  type="button"
  @click="navOpen = true"
  :aria-expanded="navOpen ? 'true' : 'false'"
  aria-controls="main-nav"
  aria-label="{{ __('Open the menu') }}"
  @if ($description !== null) aria-describedby="menu-button-description" @endif
  class="{{ $base }} {{ $variant }}"
>
  <x-icon name="menu" :size="17" :stroke-width="1.4" />

  @if ($description !== null)
    <span aria-hidden="true" class="absolute top-1.5 right-1.5 size-2 rounded-full bg-admin-signal ring-2 ring-admin-bar"></span>
    <span id="menu-button-description" class="sr-only">{{ $description }}</span>
  @endif
</button>
