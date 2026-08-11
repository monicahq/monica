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

  The `action` slot is the one thing to do about the list being empty, and only
  belongs on a list somebody can actually add to. On one they cannot, such as their
  own logs, a call to action would be a dead end.

  The link at the foot is optional and points at the documentation. There is no
  documentation portal yet, so nothing passes it today.

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
  @var \Illuminate\View\ComponentSlot|null $action
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

  @isset($action)
    <div class="mt-1">{{ $action }}</div>
  @endisset

  @if ($href && $linkLabel)
    <x-link :href="$href">{{ $linkLabel }}</x-link>
  @endif
</div>
