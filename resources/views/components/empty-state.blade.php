{{--
  What a list shows when it holds nothing. A glyph on its tint, a short title, and
  a sentence saying what will eventually land here rather than merely that nothing
  has: somebody who has just arrived learns what the list is for.

  The glyph sits four degrees off square. It is the one piece of deliberate
  imperfection in the system, and an empty list is the friendliest moment a screen
  has to spend it on.

  The panel keeps a minimum height, so the screen does not jump the moment the
  first row arrives.

  The `action` slot is the one thing to do about the list being empty, and only
  belongs on a list somebody can actually add to. On one they cannot, such as their
  own logs, a call to action would be a dead end.

  The link at the foot is optional and points at the documentation. There is no
  documentation portal yet, so nothing passes it today.

  Usage:
    <x-empty-state :title="__('No activity yet')" icon="timeline">
      <x-slot:icon><x-icon name="timeline" :size="30" :stroke-width="1.4" /></x-slot:icon>
      {{ __('Why this is empty.') }}
    </x-empty-state>

  @var string|null $title
  @var string|null $href
  @var string|null $linkLabel
  @var \Illuminate\View\ComponentSlot|null $action
--}}
@props([
  'title' => null,
  'href' => null,
  'linkLabel' => null,
])

<div {{ $attributes->class(['flex min-h-60 flex-col items-center justify-center gap-3.5 px-8 py-12 text-center']) }}>
  @isset($icon)
    <span class="flex size-16 -rotate-4 items-center justify-center rounded-lg bg-sand text-muted" aria-hidden="true">
      {{ $icon }}
    </span>
  @endisset

  @isset($title)
    <p class="font-display text-section-title text-ink">{{ $title }}</p>
  @endisset

  <p class="max-w-[44ch] text-base text-pretty text-muted">{{ $slot }}</p>

  @isset($action)
    <div class="mt-1">{{ $action }}</div>
  @endisset

  @if ($href && $linkLabel)
    <x-link :href="$href">{{ $linkLabel }}</x-link>
  @endif
</div>
