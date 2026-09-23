{{-- The verb an endpoint answers to. --}}
{{--
  @var string $method
  @var bool $tinted
--}}
@props([
  'method',
  'tinted' => false,
])

@php
  $colour = match ($method) {
    'POST' => 'text-success',
    'PUT', 'PATCH' => 'text-warning',
    'DELETE' => 'text-error',
    default => 'text-body',
  };

  $tint = match ($method) {
    'POST' => 'bg-success/10',
    'PUT', 'PATCH' => 'bg-warning/10',
    'DELETE' => 'bg-error/10',
    default => 'bg-hover',
  };
@endphp

<span
  {{
    $attributes->class([
      'font-mono text-2xs font-medium tracking-wide',
      $colour,
      'rounded-xs px-1.75 py-0.5 '.$tint => $tinted,
    ])
  }}
>{{ $method }}</span>
