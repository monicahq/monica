{{--
  The verb an endpoint answers to.

  The word is the signal, exactly as it is on a badge. The colour agrees with it
  and never replaces it, so the sidebar still says which entry deletes something
  for somebody who cannot tell the red from the green.

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
    default => 'text-brand',
  };

  $tint = match ($method) {
    'POST' => 'bg-success/10',
    'PUT', 'PATCH' => 'bg-warning/10',
    'DELETE' => 'bg-error/10',
    default => 'bg-brand/10',
  };
@endphp

<span
  {{
    $attributes->class([
      'font-mono text-[11px] font-semibold tracking-[0.02em]',
      $colour,
      'rounded-xs px-1.75 py-0.5 '.$tint => $tinted,
    ])
  }}
>{{ $method }}</span>
