{{-- The primary button: near black, one per region. --}}
{{--
  @var string|null $href
  @var string $type
  @var string $size
  @var string $variant
  @var string $tone
  @var bool $dimWhenDisabled
--}}
@props([
  'href',
  'type' => 'submit',
  'size' => 'default',
  'variant' => 'primary',
  'tone' => 'default',
  'dimWhenDisabled' => true,
])

@php
  $base = 'inline-flex cursor-pointer items-center justify-center gap-2 rounded-md border '
    .'font-sans font-medium whitespace-nowrap no-underline '
    .'transition duration-(--duration-fast) ease-out '
    .'disabled:pointer-events-none disabled:cursor-default disabled:shadow-none '
    .'aria-disabled:pointer-events-none aria-disabled:cursor-default aria-disabled:shadow-none aria-disabled:focus-visible:opacity-100 '
    .($dimWhenDisabled ? 'disabled:opacity-50 aria-disabled:opacity-50' : '');

  $danger = $tone === 'danger';

  $fill = match ($variant) {
    'secondary', 'bar' => 'border-hairline-strong bg-canvas shadow-control '
      .'hover:bg-hover hover:border-muted-soft focus-visible:border-focus '
      .'focus-ring '.($danger ? 'text-error' : 'text-ink'),
    'ghost' => 'border-transparent hover:bg-hover focus-ring '.($danger ? 'text-error' : 'text-body hover:text-ink'),
    'inverted' => 'border-transparent text-on-admin-bar-muted hover:bg-on-admin-bar/10 hover:text-on-admin-bar focus-ring-on-admin-bar',
    'danger' => 'border-transparent bg-error text-error-text hover:bg-error-hover focus-ring',
    default => 'border-transparent bg-action-primary text-action-primary-text hover:bg-action-primary-hover focus-ring',
  };

  $dimensions = match ($size) {
    'small' => 'h-8 px-3 text-sm',
    'top-bar' => 'h-8 w-8 px-0 text-sm topbar-labels:w-auto topbar-labels:px-3',
    default => 'h-9 px-3.75 text-base',
  };

  $classes = [$base, $fill, $dimensions];
@endphp

@isset($href)
  <a href="{{ $href }}" @if (! $attributes->has('data-turbo') && $attributes->whereStartsWith('x-target')->isEmpty()) data-turbo="true" @endif {{ $attributes->class($classes) }}>
    @isset($icon)
      <span class="shrink-0">{{ $icon }}</span>
    @endisset

    {{ $slot }}
  </a>
@else
  <button type="{{ $type }}" {{ $attributes->class($classes) }}>
    @isset($icon)
      <span class="shrink-0">{{ $icon }}</span>
    @endisset

    {{ $slot }}
  </button>
@endif
