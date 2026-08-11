{{--
  The neutral button, for the action that walks away from the primary one: cancel,
  back, or the way out of a screen somebody opened by mistake.

  It carries a hairline and a surface one step off the panel it sits on, so it
  reads as a button without competing with the primary beside it. Its padding is
  the primary's, and the hairline is what the primary spends on its own
  transparent border, so the two are the same height side by side.

  A button in a bar rather than at the foot of a form is drawn small, which is
  the only size besides the default one, and matches <x-button> beside it.

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
    'default' => 'py-2 text-sm [:where(&)]:px-4',
    'small' => 'py-1.5 text-[13px] [:where(&)]:px-3',
  ];

  $classes = 'relative inline-flex cursor-pointer items-center justify-center gap-2 rounded-md border border-hairline-strong bg-card font-medium whitespace-nowrap text-ink transition-colors duration-150 hover:bg-hover focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none disabled:pointer-events-none disabled:bg-disabled disabled:text-on-disabled '.$sizes[$size];
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
