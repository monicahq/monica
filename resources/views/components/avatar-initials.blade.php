{{--
  The initials of somebody, in a circle, for when there is no picture to show.

  The circle is not grey. Each name lands on one of seven warm tints, picked from
  the letters of the name itself, so the same person is always the same colour
  wherever they appear and two people beside each other are usually not. Picking
  from the name rather than storing a choice means nothing has to be decided when
  somebody is written down, and nothing has to be migrated if the tints change.

  A generated face is never drawn here. Either it is the photograph somebody chose
  or it is their letters.

  `tone="shell"` is for the one at the foot of the navigation column. That column is
  dark chrome whichever theme is on, so the letters there need the colour the dark
  theme would have given them even when the rest of the screen is light.

  @var string $name
  @var int $size
  @var string $tone
--}}
@props([
  'name',
  'size' => 34,
  'tone' => 'default',
])

@php
  /*
   * A generated avatar is content rather than interface, so it keeps a colour. The
   * eight are the vault colours, which are the only palette in the product a user
   * has already been offered, and each is drawn on a tenth of itself so the letters
   * stay legible against it in both themes.
   */
  $tints = $tone === 'shell' ? [
    'bg-[#3f5fd0]/22 text-[#93a7f0]',
    'bg-[#4f8a49]/22 text-[#5faa6e]',
    'bg-[#9a6a24]/22 text-[#d9a441]',
    'bg-[#9a3f2b]/22 text-[#dd7f66]',
    'bg-[#6b4fa8]/22 text-[#b49ae4]',
    'bg-[#2f7f86]/22 text-[#5fb3bb]',
    'bg-[#a8447a]/22 text-[#e08ab8]',
    'bg-[#4a4a52]/22 text-[#a9a9b3]',
  ] : [
    'bg-[#3f5fd0]/12 text-[#3f5fd0] dark:text-[#93a7f0]',
    'bg-[#4f8a49]/12 text-[#3f7239] dark:text-[#5faa6e]',
    'bg-[#9a6a24]/12 text-[#7f571d] dark:text-[#d9a441]',
    'bg-[#9a3f2b]/12 text-[#8a3826] dark:text-[#dd7f66]',
    'bg-[#6b4fa8]/12 text-[#5b4192] dark:text-[#b49ae4]',
    'bg-[#2f7f86]/12 text-[#26686e] dark:text-[#5fb3bb]',
    'bg-[#a8447a]/12 text-[#8e3866] dark:text-[#e08ab8]',
    'bg-[#4a4a52]/12 text-[#4a4a52] dark:text-[#a9a9b3]',
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
