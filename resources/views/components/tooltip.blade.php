{{--
  What the mark beside it means, said in full while the pointer rests on it.

  A glyph is short and a glyph is ambiguous, and the two go together: anything
  small enough to sit inside a line of text is too small to say what it stands
  for. This is where it says it, so the row keeps its shape and the meaning is
  still one gesture away.

  It is drawn rather than left to the browser's own `title`, which arrives about
  a second late, cannot be styled, and is the one tooltip a touch screen never
  shows. The bubble is the browser's only in the sense that the browser is where
  it appears.

  The name is on this wrapper and not on what it holds. A mark and the sentence
  explaining it are one thing to anybody who cannot see the bubble, so the
  wrapper is the image, the sentence is its name, and everything inside is
  hidden from assistive technology, bubble included. Saying it twice is the
  usual way this component is got wrong.

  It sits above what it explains. Rows are laid out one under the other in a
  panel that clips, so a bubble drawn underneath the last row of one would be
  cut in half; there is always the row above to grow into.

  It opens on focus as well as under the pointer. A sentence only a pointer can
  reach is a sentence somebody working from the keyboard never gets, and hover
  alone is the usual way this is got wrong. `focusable` is what puts the wrapper
  in the tab order, and a tooltip explaining something already focusable, such as
  the glyph inside a button, leaves it off.

  @var string $text
  @var bool $focusable
--}}
@props([
  'text',
  'focusable' => false,
])

<span
  x-data="{ shown: false }"
  @mouseenter="shown = true"
  @mouseleave="shown = false"
  @focusin="shown = true"
  @focusout="shown = false"
  @keydown.escape="shown = false"
  @if ($focusable) tabindex="0" @endif
  role="img"
  aria-label="{{ $text }}"
  {{ $attributes->class(['relative inline-flex items-center', 'rounded-sm focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none' => $focusable]) }}
>
  {{ $slot }}

  <span
    x-show="shown"
    x-cloak
    x-transition.opacity
    aria-hidden="true"
    class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-1.5 w-max max-w-3xs -translate-x-1/2 rounded-md bg-primary px-2.5 py-1.5 text-xs text-balance text-on-primary shadow-pop"
  >{{ $text }}</span>
</span>
