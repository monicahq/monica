{{-- A run of labelled values, one to a line. --}}
{{--
  @var string $labelWidth
  @var string $labelStyle
--}}
@props([
  'labelWidth' => '140px',
  'labelStyle' => 'plain',
])

<dl {{ $attributes->class(['grid gap-0']) }} style="--detail-label-width: {{ $labelWidth }}">{{ $slot }}</dl>
