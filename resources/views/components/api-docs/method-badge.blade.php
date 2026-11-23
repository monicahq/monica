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
    'QUERY' => 'text-query',
    default => 'text-body',
  };

  $tint = match ($method) {
    'POST' => 'border-success-edge bg-success-tint',
    'PUT', 'PATCH' => 'border-warning-edge bg-warning-tint',
    'DELETE' => 'border-error-edge bg-error-tint',
    'QUERY' => 'border-query-edge bg-query-tint',
    default => 'border-hairline bg-card',
  };
@endphp

<span
  {{
    $attributes->class([
      'font-mono text-2xs font-medium tracking-wide',
      $colour,
      'rounded-sm border px-2 py-px '.$tint => $tinted,
    ])
  }}
>{{ $method }}</span>
