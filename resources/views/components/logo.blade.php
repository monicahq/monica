{{--
  The mark of the application, drawn once in `public/images/logo.svg` and shown
  wherever the product signs its name: the head of the sidebar, and the card
  every screen somebody signs in from is built around.

  The size given is the height. The mark is slightly wider than it is tall, so
  the width follows from it rather than being forced square, which would squash
  it. Both are written on the tag as well as in the style, so the room it takes
  is reserved before it arrives and nothing beside it jumps.

  It is hidden from screen readers. The name of the product is written beside it
  every place it appears, and hearing it twice says nothing the first one did
  not.

  @var int $size
--}}
@props([
  'size' => 30,
])

@php
  $width = (int) round($size * 32 / 29);
@endphp

<img
  src="{{ asset('images/logo.svg') }}"
  alt=""
  width="{{ $width }}"
  height="{{ $size }}"
  decoding="async"
  {{ $attributes->class(['shrink-0']) }}
  style="width: {{ $width }}px; height: {{ $size }}px;"
  aria-hidden="true"
/>
