{{--
  A short word about the state or the kind of something.

  The word is the signal. The tint behind it only repeats what the word already
  says, so a badge still works for somebody who cannot see the colour, and a
  badge with no word in it is a bug rather than a decoration.

  It is never a pill: 5px corners, which is the smallest radius in the system.

  @var string $tone
  @var string|null $icon
--}}
@props([
  'tone' => 'neutral',
  'icon' => null,
])

@php
  /*
   * A badge is a hairline, a barely tinted ground and a word. The word is what
   * carries the state; the tint only agrees with it, which is why every tone below
   * still reads correctly with the colour taken away.
   */
  $tint = match ($tone) {
    'success' => 'border-success/30 bg-success/10 text-success',
    'warning' => 'border-warning/30 bg-warning/10 text-warning',
    'danger' => 'border-error/30 bg-error/10 text-error',
    'info' => 'border-brand/30 bg-brand/10 text-brand',
    'accent' => 'border-brand/30 bg-accent-tint text-brand',
    default => 'border-hairline bg-sand text-ink-soft',
  };
@endphp

<span
  {{
    $attributes->class([
      'inline-flex h-6 items-center gap-1.25 rounded-xs border px-2.25 font-sans text-[12.5px] font-semibold tracking-[0.005em] whitespace-nowrap',
      $tint,
    ])
  }}
>
  @if ($icon)
    <x-icon :name="$icon" :size="13" :stroke-width="1.6" />
  @endif

  {{ $slot }}
</span>
