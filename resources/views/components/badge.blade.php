{{--
  A short word about the state or the kind of something.

  The word is the signal. The tint behind it only repeats what the word already
  says, so a badge still works for somebody who cannot see the colour, and a
  badge with no word in it is a bug rather than a decoration.

  It is a pill. Nothing else in the system is fully round except an avatar, which
  is what makes a badge legible as a badge from across a screen.

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
    'success' => 'border-success-edge bg-success-tint text-success',
    'warning' => 'border-warning-edge bg-warning-tint text-warning',
    'danger' => 'border-error-edge bg-error-tint text-error',
    // There is no accent colour left, so the two tones that carried the blue are
    // told apart by how loud they are instead: an outline, and a filled one.
    'info' => 'border-hairline bg-canvas text-body',
    'accent' => 'border-transparent bg-primary text-on-primary',
    default => 'border-transparent bg-hover text-body',
  };
@endphp

<span
  {{
    $attributes->class([
      'inline-flex h-5.5 items-center gap-1.25 rounded-full border px-2.25 font-sans text-[11.5px] font-medium whitespace-nowrap',
      $tint,
    ])
  }}
>
  @if ($icon)
    <x-icon :name="$icon" :size="13" :stroke-width="1.6" />
  @endif

  {{ $slot }}
</span>
