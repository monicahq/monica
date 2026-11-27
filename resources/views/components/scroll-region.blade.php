{{-- A wide block that scrolls sideways, reachable by keyboard and named by the heading it sits under. --}}
{{--
  @var string $labelledby
  @var string $role
--}}
@props([
  'labelledby',
  'role' => 'region',
])

<div {{ $attributes->class(['overflow-x-auto focus-ring-inset']) }} tabindex="0" role="{{ $role }}" aria-labelledby="{{ $labelledby }}">
  {{ $slot }}
</div>
