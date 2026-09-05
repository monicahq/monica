{{-- A picture whose size is reserved before it arrives, so nothing on the page jumps. --}}
{{--
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
