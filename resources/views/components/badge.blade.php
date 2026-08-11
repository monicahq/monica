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
  $tint = match ($tone) {
    'success' => 'bg-forest-tint text-forest-deep',
    'warning' => 'bg-marigold-tint text-marigold-deep',
    'danger' => 'bg-error-tint text-error',
    'info' => 'bg-indigo-tint text-indigo-deep',
    'accent' => 'bg-accent-tint text-terracotta-deep',
    default => 'bg-sand text-ink-soft',
  };
@endphp

<span
  {{
    $attributes->class([
      'inline-flex h-6 items-center gap-1.25 rounded-xs px-2.25 font-sans text-[12.5px] font-semibold tracking-[0.005em] whitespace-nowrap',
      $tint,
    ])
  }}
>
  @if ($icon)
    <x-icon :name="$icon" :size="13" :stroke-width="1.6" />
  @endif

  {{ $slot }}
</span>
