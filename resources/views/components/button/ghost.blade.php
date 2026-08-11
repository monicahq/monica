{{--
  The quietest button: no fill and no edge until the pointer is on it.

  It is for an action that belongs beside a heavier one without being offered as
  hard, such as removing the photo that has just been chosen. It is still a button
  and still reaches 34 or 40px, so it is not a link pretending to be one.

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
  $classes = ['erica-btn erica-btn--ghost', $size === 'small' ? 'erica-btn--sm' : 'erica-btn--md'];
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
