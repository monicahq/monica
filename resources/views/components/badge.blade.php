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

<span {{ $attributes->class(['erica-badge', 'erica-badge--'.$tone]) }}>
  @if ($icon)
    <x-icon :name="$icon" :size="13" :stroke-width="1.6" />
  @endif

  {{ $slot }}
</span>
