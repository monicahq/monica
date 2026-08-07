{{--
  A picture, with the size it will take reserved before it arrives so nothing
  on the page jumps once it does.

  @var string $src
  @var string $alt
  @var int $width
  @var int $height
  @var string|null $srcset
  @var string $loading
--}}
@props([
  'src',
  'alt' => '',
  'width',
  'height',
  'srcset' => null,
  'loading' => 'lazy',
])

{{-- The browser defers the download until the image nears the viewport on its own. --}}
<img
  src="{{ $src }}"
  @if ($srcset) srcset="{{ $srcset }}" @endif
  alt="{{ $alt }}"
  width="{{ $width }}"
  height="{{ $height }}"
  loading="{{ $loading }}"
  decoding="async"
  {{ $attributes }}
/>
