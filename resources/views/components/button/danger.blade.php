{{--
  The filled red button, which appears only at the last step of something that
  cannot be undone.

  A destructive action never starts here. It starts as a secondary or a ghost
  button that opens the confirmation, and this is what the confirmation ends with,
  once the consequence has been named.

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
  $classes = ['erica-btn erica-btn--danger', $size === 'small' ? 'erica-btn--sm' : 'erica-btn--md'];
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
