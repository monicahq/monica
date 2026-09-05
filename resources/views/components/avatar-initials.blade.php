{{-- The initials of somebody, in a circle, for when there is no picture to show. --}}
{{--
  @var string $name
  @var int $size
--}}
@props([
  'name',
  'size' => 34,
])

@php
  $tints = array_map(
    fn (\App\Enums\VaultColourEnum $colour): string => $colour->tint(),
    \App\Enums\VaultColourEnum::cases(),
  );

  $words = collect(preg_split('/\s+/', trim($name)))->filter()->values();

  $initials = $words->isEmpty()
    ? '?'
    : mb_strtoupper(mb_substr($words->first(), 0, 1).($words->count() > 1 ? mb_substr($words->last(), 0, 1) : ''));

  $sum = collect(mb_str_split($name))->reduce(fn (int $total, string $letter): int => ($total + mb_ord($letter)) % 997, 0);

  $tint = $tints[$sum % count($tints)];
@endphp

<span
  {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-full font-bold tracking-tight select-none', $tint]) }}
  style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.38) }}px"
  aria-hidden="true"
>{{ $initials }}</span>
