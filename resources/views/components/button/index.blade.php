{{--
  The primary button: terracotta, one per region. It renders an anchor when it is
  given an href, and a button otherwise. The icon slot sits before the label.

  Its physicality comes from a hard bottom edge and a press that moves it down one
  pixel. Nothing about it grows or scales.

  A button in a bar rather than at the foot of a form is drawn small, which is the
  only size besides the default one.

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
  $classes = ['erica-btn erica-btn--primary', $size === 'small' ? 'erica-btn--sm' : 'erica-btn--md'];
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
