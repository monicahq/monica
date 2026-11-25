{{-- The Markdown mark, drawn in the colour of the text around it. --}}
{{--
  @var int $size
  @var string|null $label
--}}
@props([
  'size' => 12,
  'label' => null,
])

<svg
  width="{{ (int) round($size * 208 / 128) }}"
  height="{{ $size }}"
  viewBox="0 0 208 128"
  fill="currentColor"
  @if ($label)
    role="img"
    aria-label="{{ $label }}"
  @else
    aria-hidden="true"
  @endif
  {{ $attributes->class(['block shrink-0']) }}
>@if ($label)<title>{{ $label }}</title>@endif<path fill-rule="evenodd" clip-rule="evenodd" d="M15 10a5 5 0 0 0-5 5v98a5 5 0 0 0 5 5h178a5 5 0 0 0 5-5V15a5 5 0 0 0-5-5zM0 15A15 15 0 0 1 15 0h178a15 15 0 0 1 15 15v98a15 15 0 0 1-15 15H15a15 15 0 0 1-15-15z"></path><path d="M30 98V30h20l20 25 20-25h20v68H90V59L70 84 50 59v39zm125 0-30-33h20V30h20v35h20z"></path></svg>
