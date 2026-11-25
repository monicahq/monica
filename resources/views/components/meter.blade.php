{{-- A bar showing how much of a whole is reached, with its value given to assistive technologies. --}}
{{--
  @var int $value
  @var string $label
  @var string $role
--}}
@props([
  'value',
  'label',
  'role' => 'meter',
])

@php
  $width = max(0, min(100, (int) $value));
@endphp

<span
  role="{{ $role === 'progressbar' ? 'progressbar' : 'meter' }}"
  aria-valuemin="0"
  aria-valuemax="100"
  aria-valuenow="{{ $width }}"
  aria-label="{{ $label }}"
  {{ $attributes->class(['block h-1.5 w-full overflow-hidden rounded-full bg-hover']) }}
>
  <span class="block h-full rounded-full bg-ink/70" style="width: {{ $width }}%"></span>
</span>
