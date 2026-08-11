{{--
  What a list shows when it holds nothing. A glyph on its tint, a short title, and
  a sentence saying what will eventually land here rather than merely that nothing
  has: somebody who has just arrived learns what the list is for.

  The glyph sits four degrees off square. It is the one piece of deliberate
  imperfection in the system, and an empty list is the friendliest moment a screen
  has to spend it on.

  The panel keeps a minimum height, so the screen does not jump the moment the
  first row arrives.

  `hue` and `tint` are the colour of whatever kind of thing the list holds. Left
  alone they are neutral, which is right for a list of records rather than of
  objects.

  The link at the foot is optional and points at the documentation. There is no
  documentation portal yet, so nothing passes it today. Nothing else belongs
  there: on a list somebody cannot add to, a call to action would be a dead end.

  Usage:
    <x-empty-state :title="__('No activity yet')" hue="var(--forest)" tint="var(--forest-tint)">
      <x-slot:icon><x-icon name="timeline" :size="30" :stroke-width="1.4" /></x-slot:icon>
      {{ __('Why this is empty.') }}
    </x-empty-state>

  @var string|null $title
  @var string $hue
  @var string $tint
  @var string|null $href
  @var string|null $linkLabel
--}}
@props([
  'title' => null,
  'hue' => 'var(--muted)',
  'tint' => 'var(--sand)',
  'href' => null,
  'linkLabel' => null,
])

<div {{ $attributes->class(['erica-empty']) }}>
  @isset($icon)
    <span class="erica-empty__glyph" style="background: {{ $tint }}; color: {{ $hue }}" aria-hidden="true">
      {{ $icon }}
    </span>
  @endisset

  @isset($title)
    <p class="erica-empty__title">{{ $title }}</p>
  @endisset

  <p class="erica-empty__text">{{ $slot }}</p>

  @if ($href && $linkLabel)
    <x-link :href="$href">{{ $linkLabel }}</x-link>
  @endif
</div>
