{{--
  The initials of somebody, in a circle, for when there is no picture to show.

  The circle is not grey. Each name lands on one of seven warm tints, picked from
  the letters of the name itself, so the same person is always the same colour
  wherever they appear and two people beside each other are usually not. Picking
  from the name rather than storing a choice means nothing has to be decided when
  somebody is written down, and nothing has to be migrated if the tints change.

  A generated face is never drawn here. Either it is the photograph somebody chose
  or it is their letters.

  @var string $name
  @var int $size
--}}
@props([
  'name',
  'size' => 34,
])

@php
  $tints = [
    'bg-terracotta-tint text-terracotta-deep',
    'bg-marigold-tint text-marigold-deep',
    'bg-forest-tint text-forest-deep',
    'bg-indigo-tint text-indigo-deep',
    'bg-plum-tint text-plum-deep',
    'bg-rose-tint text-rose',
    'bg-sky-tint text-sky',
  ];

  $words = collect(preg_split('/\s+/', trim($name)))->filter()->values();

  $initials = $words->isEmpty()
    ? '?'
    : mb_strtoupper(mb_substr($words->first(), 0, 1).($words->count() > 1 ? mb_substr($words->last(), 0, 1) : ''));

  /*
   * The letters added up, wrapped at a prime so a long name does not drift towards
   * the same total as every other long name.
   */
  $sum = collect(mb_str_split($name))->reduce(fn (int $total, string $letter): int => ($total + mb_ord($letter)) % 997, 0);

  $tint = $tints[$sum % count($tints)];
@endphp

<span
  {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-full font-bold tracking-tight select-none', $tint]) }}
  {{-- The size is a number rather than a step, so it is the one thing here a utility cannot carry. --}}
  style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.38) }}px"
  aria-hidden="true"
>{{ $initials }}</span>
