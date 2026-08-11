{{--
  The neutral button, for the action that walks away from the primary one: cancel,
  back, or the way out of a screen somebody opened by mistake.

  It carries a hairline and a white surface, so it reads as a button without
  competing with the primary beside it. Its heights are the primary's, so the two
  line up side by side.

  @var string|null $href
  @var string $type
  @var string $size
--}}
@props([
  'href',
  'type' => 'submit',
  'size' => 'default',
])

@php
  $classes = ['erica-btn erica-btn--secondary', $size === 'small' ? 'erica-btn--sm' : 'erica-btn--md'];
@endphp

@isset($href)
  <a href="{{ $href }}" {{ $attributes->class($classes) }}>
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
