{{-- A moment, machine readable in the markup and spelled out in full on hover. --}}
{{-- @var \Carbon\CarbonInterface $at --}}
@props([
  'at',
])

<time datetime="{{ $at->toIso8601String() }}" title="{{ $at->toDayDateTimeString() }}" {{ $attributes }}>{{ $slot }}</time>
