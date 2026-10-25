{{-- The primary button: near black, one per region. --}}
{{--
  @var string|null $href
  @var string $type
  @var string $size
  @var string $variant
  @var string $tone
--}}
@props([
  'href',
  'type' => 'submit',
  'size' => 'default',
  'variant' => 'primary',
  'tone' => 'default',
])

@php
  $base = 'inline-flex cursor-pointer items-center justify-center gap-2 rounded-md border '
    .'font-sans font-medium whitespace-nowrap no-underline '
    .'transition duration-(--duration-fast) ease-out '
    .'focus-ring '
    .'disabled:pointer-events-none disabled:cursor-default disabled:opacity-50 disabled:shadow-none '
    .'aria-disabled:pointer-events-none aria-disabled:cursor-default aria-disabled:opacity-50 aria-disabled:shadow-none';

  $danger = $tone === 'danger';

  $fill = match ($variant) {
    'secondary', 'bar' => 'border-hairline-strong bg-canvas shadow-control '
      .'hover:bg-hover hover:border-muted-soft focus-visible:border-focus '
      .($danger ? 'text-error' : 'text-ink'),
    'ghost' => 'border-transparent hover:bg-hover '.($danger ? 'text-error' : 'text-body hover:text-ink'),
    'danger' => 'border-transparent bg-error text-error-text hover:bg-error-hover',
    default => 'border-transparent bg-action-primary text-action-primary-text hover:bg-action-primary-hover',
  };

  $classes = [$base, $fill, $size === 'small' ? 'h-8 px-3 text-sm' : 'h-9 px-3.75 text-base'];
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
