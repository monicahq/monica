{{--
  The primary button: terracotta, one per region. It renders an anchor when it is
  given an href, and a button otherwise. The icon slot sits before the label.

  Its physicality comes from a hard bottom edge and a press that moves it down one
  pixel. Nothing about it grows or scales.

  A button in a bar rather than at the foot of a form is drawn small, which is the
  only size besides the default one.

  This file also carries the other four variants, because the anchor-or-button
  markup and every utility except the fill are the same for all five. Callers reach
  them through <x-button.secondary>, <x-button.bar>, <x-button.ghost> and
  <x-button.danger> rather than by naming a variant here.

  @var string|null $href
  @var string $type
  @var string $size
  @var string $variant
--}}
@props([
  'href',
  'type' => 'submit',
  'size' => 'default',
  'variant' => 'primary',
])

@php
  $base = 'inline-flex cursor-pointer items-center justify-center gap-2 rounded-md border '
    .'border-transparent font-sans font-semibold tracking-[-0.005em] whitespace-nowrap no-underline '
    .'transition-[background-color,box-shadow,transform,border-color] duration-(--duration-fast) ease-out '
    .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring '
    .'active:translate-y-(--press-translate) active:shadow-none '
    .'disabled:pointer-events-none disabled:cursor-default disabled:opacity-50 disabled:shadow-none '
    .'aria-disabled:pointer-events-none aria-disabled:cursor-default aria-disabled:opacity-50 aria-disabled:shadow-none';

  $fill = match ($variant) {
    'secondary' => 'border-hairline-strong bg-canvas text-ink shadow-raised hover:bg-sand',
    'bar' => 'border-hairline-strong bg-sand text-ink shadow-raised hover:border-ink hover:bg-sand-deep',
    'ghost' => 'text-ink-soft hover:bg-sand hover:text-ink',
    'danger' => 'bg-error text-white shadow-btn-danger hover:bg-error-hover',
    default => 'bg-action-primary text-action-primary-text shadow-btn-primary hover:bg-action-primary-hover',
  };

  $classes = [$base, $fill, $size === 'small' ? 'h-8.5 px-3.25 text-[13.5px]' : 'h-10 px-4.5 text-base'];
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
