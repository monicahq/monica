{{--
  A control that is a glyph and nothing else, with the words it stands for.

  A row of these is unreadable without them, so the name is not optional: it is
  what a screen reader announces, and it is what the bubble says to everybody
  else. Both come from the same prop, so they cannot drift apart.

  It renders an anchor when it is given an href, and a button otherwise, the same
  way <x-button> does. A control that goes somewhere has to be a link: the middle
  click, the context menu and "open in a new tab" all belong to the browser, and a
  button that navigates takes them away.

  The bubble opens on focus as well as under the pointer. A sentence only a
  pointer can reach is a sentence nobody working from the keyboard ever gets.

  It is not <x-tooltip> around a button. That component is `role="img"`, which
  takes everything inside it out of the accessibility tree, and a button in
  there stops existing for anybody listening. Putting the tooltip inside the
  button instead fixes that and breaks the other half, since `focusin` travels
  up from the button and never reaches a listener below it. So the state lives
  on the button here, which is the one element that is both the target and the
  thing being explained.

  `align` says which edge of the bubble is pinned to the button. A control at
  the right edge of a row centres its bubble half off the page, so a cluster of
  them there asks for `right` and the bubble grows inwards instead.

  @var string $label
  @var string $icon
  @var string|null $href
  @var string $type
  @var string $align
--}}
@props([
  'label',
  'icon',
  'href' => null,
  'type' => 'button',
  'align' => 'center',
])

@php
  $classes = [
    'relative flex size-7.5 flex-none cursor-pointer items-center justify-center rounded-md border border-hairline bg-canvas text-muted no-underline',
    'transition-colors duration-(--duration-fast) ease-out hover:border-hairline-strong hover:text-ink',
    'focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none',
  ];

  /* The bubble is the same in both forms, and it is the reason this markup is
     assembled here rather than written twice: the two branches differ by one
     element and one attribute, and a second copy of the bubble is a second
     thing to keep in step. */
  $hover = [
    'x-data' => '{ shown: false }',
    '@mouseenter' => 'shown = true',
    '@mouseleave' => 'shown = false',
    '@focus' => 'shown = true',
    '@blur' => 'shown = false',
    '@keydown.escape' => 'shown = false',
    'aria-label' => $label,
  ];
@endphp

@isset($href)
  <a href="{{ $href }}" {{ $attributes->merge($hover)->class($classes) }}>
@else
  <button type="{{ $type }}" {{ $attributes->merge($hover)->class($classes) }}>
@endisset

  <x-icon :name="$icon" :size="15" :stroke-width="1.6" />

  <span
    x-show="shown"
    x-cloak
    x-transition.opacity
    aria-hidden="true"
    @class([
      'pointer-events-none absolute bottom-full z-20 mb-1.5 w-max max-w-3xs rounded-md bg-primary px-2.5 py-1.5 text-xs text-balance text-on-primary shadow-pop',
      'left-1/2 -translate-x-1/2' => $align !== 'right',
      'right-0' => $align === 'right',
    ])
  >{{ $label }}</span>

@isset($href)
  </a>
@else
  </button>
@endisset
