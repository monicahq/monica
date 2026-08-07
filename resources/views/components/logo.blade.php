{{--
  The mark of the application: the first letter of its name in a rounded square.

  @var int $size
--}}
@props([
  'size' => 30,
])

<span
  {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-md bg-primary font-semibold tracking-tight text-on-primary']) }}
  style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.47) }}px;"
  aria-hidden="true"
>O</span>
