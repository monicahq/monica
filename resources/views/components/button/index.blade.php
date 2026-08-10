{{--
  The primary button. It renders an anchor when it is given an href, and a button
  otherwise. The icon slot sits before the label.

  The border is transparent and draws nothing. It is there so the button is the
  same height as <x-button.secondary>, which spends the same two pixels on a
  hairline, and the two line up when they sit side by side.

  A button in a bar rather than at the foot of a form is drawn small, which is
  the only size besides the default one.

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
  $sizes = [
    'default' => 'py-2 text-sm [:where(&)]:px-5',
    'small' => 'py-1.5 text-[13px] [:where(&)]:px-3.5',
  ];

  $classes = 'relative inline-flex cursor-pointer items-center justify-center gap-2 rounded-md border border-transparent font-semibold whitespace-nowrap transition-colors duration-150 bg-accent text-accent-foreground hover:bg-accent/88 focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none disabled:pointer-events-none disabled:bg-disabled disabled:text-on-disabled '.$sizes[$size];
@endphp

@isset($href)
  <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
      <span class="shrink-0">{{ $icon }}</span>
    @endisset

    {{ $slot }}
  </a>
@else
  <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
      <span class="shrink-0">{{ $icon }}</span>
    @endisset

    {{ $slot }}
  </button>
@endif
