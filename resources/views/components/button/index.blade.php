{{--
  The primary button: near black, one per region. It renders an anchor when it is
  given an href, and a button otherwise. The icon slot sits before the label.

  It is flat. There is no bottom edge, no shadow and no press that moves it: what
  answers a pointer is the fill going one step lighter, and what answers a
  keyboard is a ring drawn three pixels clear of the button so it reads against
  the page rather than against the fill.

  The label is set in medium rather than semibold. A page of semibold buttons
  reads as a row of labels shouting, and the weight is not what says a button is
  a button; the fill and the border are.

  This file also carries the other four variants, because the anchor-or-button
  markup and every utility except the fill are the same for all five. Callers
  reach them through <x-button.secondary>, <x-button.bar>, <x-button.ghost> and
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
    .'border-transparent font-sans font-medium whitespace-nowrap no-underline '
    .'transition-[background-color,box-shadow,border-color] duration-(--duration-fast) ease-out '
    .'focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none '
    .'disabled:pointer-events-none disabled:cursor-default disabled:opacity-50 disabled:shadow-none '
    .'aria-disabled:pointer-events-none aria-disabled:cursor-default aria-disabled:opacity-50 aria-disabled:shadow-none';

  $fill = match ($variant) {
    // A button in a bar and a button beside a primary one are now drawn the same
    // way, because the bar is the same white as the page it sits on.
    'secondary', 'bar' => 'border-hairline bg-canvas text-ink hover:bg-hover',
    'ghost' => 'text-body hover:bg-hover hover:text-ink',
    'danger' => 'bg-error text-white hover:bg-error-hover',
    default => 'bg-action-primary text-action-primary-text hover:bg-action-primary-hover',
  };

  $classes = [$base, $fill, $size === 'small' ? 'h-8 px-3 text-sm' : 'h-9 px-3.75 text-base'];
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
