{{-- A control that is a glyph and nothing else, with the words it stands for. --}}
{{--
  @var string $label
  @var string $icon
  @var string|null $href
  @var string $type
  @var string $align
--}}
@props([
  'label',
  'icon',
  'href' => null,
  'type' => 'button',
  'align' => 'center',
])

@php
  $classes = [
    'relative flex size-7.5 flex-none cursor-pointer items-center justify-center rounded-md border border-hairline bg-canvas text-muted no-underline',
    'transition-colors duration-(--duration-fast) ease-out hover:border-hairline-strong hover:text-ink',
    'focus-ring',
  ];

  $hover = [
    'x-data' => '{ shown: false }',
    '@mouseenter' => 'shown = true',
    '@mouseleave' => 'shown = false',
    '@focus' => 'shown = true',
    '@blur' => 'shown = false',
    '@keydown.escape' => 'shown = false',
    'aria-label' => $label,
  ];
@endphp

@isset($href)
  <a href="{{ $href }}" @if (! $attributes->has('data-turbo') && $attributes->whereStartsWith('x-target')->isEmpty()) data-turbo="true" @endif {{ $attributes->merge($hover)->class($classes) }}>
@else
  <button type="{{ $type }}" {{ $attributes->merge($hover)->class($classes) }}>
@endisset

  <x-icon :name="$icon" :size="15" :stroke-width="1.6" />

  <span
    x-show="shown"
    x-cloak
    x-transition.opacity
    aria-hidden="true"
    @class([
      'pointer-events-none absolute bottom-full z-20 mb-1.5 w-max max-w-3xs rounded-md bg-primary px-2.5 py-1.5 text-xs text-balance text-on-primary shadow-pop',
      'left-1/2 -translate-x-1/2' => $align !== 'right',
      'right-0' => $align === 'right',
    ])
  >{{ $label }}</span>

@isset($href)
  </a>
@else
  </button>
@endisset
