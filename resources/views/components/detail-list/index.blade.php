{{-- A run of labelled values, one to a line. --}}
{{--
  @var string $labelWidth
  @var string $labelStyle
  @var string $layout
--}}
@props([
  'labelWidth' => '140px',
  'labelStyle' => 'plain',
  'layout' => 'columns',
])

<dl {{ $attributes->class(['grid gap-0']) }} style="--detail-label-width: {{ $labelWidth }}">{{ $slot }}</dl>
