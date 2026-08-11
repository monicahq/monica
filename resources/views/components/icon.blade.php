{{--
  One glyph of the icon language.

  Every glyph is drawn on a 16px grid with a 1.4 stroke, round caps and joins, and
  about 2px of optical padding, so any two of them sit beside each other without
  one looking heavier than the other. Nothing here is filled: the stroke is
  `currentColor`, which is what lets a row tint its mark by setting a colour on the
  wrapper rather than by asking for a different drawing.

  A glyph is decoration by default and is hidden from screen readers, because the
  thing beside it is already named. One that is the only content of a control is
  given a `label` instead, and then it becomes an image with a title.

  A name nothing answers to falls back to the default rather than drawing nothing,
  since an empty square beside a row reads as a bug.

  This is the whole set. There is no icon font, no third-party pack and no emoji:
  a glyph that does not exist here has to be drawn here.

  @var string $name
  @var int $size
  @var float $strokeWidth
  @var string|null $label
--}}
@props([
  'name' => 'profile',
  'size' => 18,
  'strokeWidth' => 1.4,
  'label' => null,
])

@php
  $paths = [
    'profile' => '<circle cx="8" cy="6" r="2.6"></circle><path d="M3 14c0-2.6 2.2-4 5-4s5 1.4 5 4"></path>',
    'people' => '<circle cx="6" cy="6.2" r="2.3"></circle><path d="M1.9 13.4c0-2.3 1.9-3.6 4.1-3.6s4.1 1.3 4.1 3.6"></path><path d="M11 4.3a2.2 2.2 0 0 1 0 4.2"></path><path d="M11.7 10.2c1.6.3 2.8 1.4 2.8 3.2"></path>',
    'relationship' => '<circle cx="4.4" cy="5.4" r="2.2"></circle><circle cx="11.6" cy="10.6" r="2.2"></circle><path d="M6.3 7.1 9.7 8.9"></path>',
    'timeline' => '<line x1="4" y1="2.6" x2="4" y2="13.4"></line><circle cx="4" cy="5.2" r="1.4"></circle><circle cx="4" cy="10.8" r="1.4"></circle><line x1="7.2" y1="5.2" x2="13.4" y2="5.2"></line><line x1="7.2" y1="10.8" x2="12" y2="10.8"></line>',
    'memory' => '<rect x="2.2" y="3.4" width="11.6" height="9.2" rx="1.6"></rect><path d="m3.5 11.3 2.9-3.1 2.3 2.2 2-1.8 2 2.2"></path><circle cx="10.4" cy="6.4" r="1"></circle>',
    'note' => '<path d="M4 2.4h5.2L12.4 5.6V13.6H4z"></path><line x1="6.2" y1="8" x2="10.2" y2="8"></line><line x1="6.2" y1="10.6" x2="10.2" y2="10.6"></line>',
    'reminder' => '<path d="M4.4 11.2V7.6a3.6 3.6 0 0 1 7.2 0v3.6"></path><line x1="3" y1="11.2" x2="13" y2="11.2"></line><path d="M6.8 13.1a1.6 1.6 0 0 0 2.4 0"></path>',
    'activity' => '<path d="M2.2 8.6h3L6.8 4l2.4 8 1.6-3.4h3"></path>',
    'place' => '<path d="M8 14c2.6-3 4-5.1 4-7A4 4 0 0 0 4 7c0 1.9 1.4 4 4 7Z"></path><circle cx="8" cy="6.9" r="1.5"></circle>',
    'pet' => '<ellipse cx="4.9" cy="5.6" rx="1.3" ry="1.7"></ellipse><ellipse cx="8.6" cy="4.4" rx="1.3" ry="1.8"></ellipse><ellipse cx="12" cy="6.3" rx="1.3" ry="1.6"></ellipse><path d="M8.2 8.4c2 0 3.6 1.4 3.6 3 0 1.2-1 1.9-2.2 1.6-1-.3-1.8-.3-2.8 0-1.2.3-2.2-.4-2.2-1.6 0-1.6 1.6-3 3.6-3Z"></path>',
    'organization' => '<path d="M3 13.4V3.6a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v9.8"></path><path d="M10 6.6h2.9a1 1 0 0 1 1 1v5.8"></path><line x1="5.2" y1="5.4" x2="7.8" y2="5.4"></line><line x1="5.2" y1="8" x2="7.8" y2="8"></line><line x1="1.8" y1="13.4" x2="14.2" y2="13.4"></line>',
    'gift' => '<rect x="2.4" y="6.6" width="11.2" height="6.8" rx="1.2"></rect><line x1="1.8" y1="6.6" x2="14.2" y2="6.6"></line><line x1="8" y1="6.6" x2="8" y2="13.4"></line><path d="M8 6.6C6.6 4.6 5.6 2.8 4.2 3.4 2.9 4 3.6 6.2 8 6.6Z"></path><path d="M8 6.6c1.4-2 2.4-3.8 3.8-3.2 1.3.6.6 2.8-3.8 3.2Z"></path>',
    'family' => '<path d="M2.6 7.4 8 3.2l5.4 4.2"></path><path d="M4 8.6v4.8h8V8.6"></path><circle cx="6.4" cy="10.8" r="1"></circle><circle cx="9.6" cy="10.8" r="1"></circle>',
    'journal' => '<path d="M8 4.6C6.8 3.6 5.2 3.2 3 3.2v8.6c2.2 0 3.8.4 5 1.4 1.2-1 2.8-1.4 5-1.4V3.2c-2.2 0-3.8.4-5 1.4Z"></path><line x1="8" y1="4.6" x2="8" y2="13.2"></line>',
    'tasks' => '<path d="M2.6 5.2 4 6.6l2.4-2.6"></path><path d="M2.6 11 4 12.4l2.4-2.6"></path><line x1="8.6" y1="5.6" x2="13.6" y2="5.6"></line><line x1="8.6" y1="11.4" x2="13.6" y2="11.4"></line>',
    'vault' => '<rect x="2.2" y="3" width="11.6" height="10" rx="1.6"></rect><circle cx="7.4" cy="8" r="2.4"></circle><line x1="11.8" y1="6.2" x2="11.8" y2="9.8"></line>',
    'file' => '<path d="M4 2.4h5.2L12.4 5.6V13.6H4z"></path><path d="M9.2 2.4v3.2h3.2"></path>',
    'settings' => '<circle cx="8" cy="8" r="2.2"></circle><path d="M8 1.9v1.7M8 12.4v1.7M14.1 8h-1.7M3.6 8H1.9M12.3 3.7 11.1 4.9M4.9 11.1 3.7 12.3M12.3 12.3 11.1 11.1M4.9 4.9 3.7 3.7"></path>',
    'calendar' => '<rect x="2.4" y="3.4" width="11.2" height="10.2" rx="1.5"></rect><line x1="2.4" y1="6.4" x2="13.6" y2="6.4"></line><line x1="5.4" y1="2.2" x2="5.4" y2="4.2"></line><line x1="10.6" y1="2.2" x2="10.6" y2="4.2"></line>',
    'search' => '<circle cx="7.2" cy="7.2" r="4.2"></circle><line x1="10.4" y1="10.4" x2="13.6" y2="13.6"></line>',
    'plus' => '<line x1="8" y1="3.2" x2="8" y2="12.8"></line><line x1="3.2" y1="8" x2="12.8" y2="8"></line>',
    'heart' => '<path d="M8 13.2C4.6 11 2.4 9.1 2.4 6.6A2.9 2.9 0 0 1 8 5.2a2.9 2.9 0 0 1 5.6 1.4c0 2.5-2.2 4.4-5.6 6.6Z"></path>',
    'star' => '<path d="M8 2.4 9.8 6l4 .6-2.9 2.8.7 4L8 11.5 4.4 13.4l.7-4L2.2 6.6l4-.6z"></path>',
    'mail' => '<rect x="2.2" y="3.8" width="11.6" height="8.4" rx="1.5"></rect><path d="m2.6 4.8 5.4 3.8 5.4-3.8"></path>',
    'key' => '<circle cx="10.4" cy="5.6" r="3"></circle><path d="M8.3 7.7 3 13"></path><path d="M4.6 11.4 6 12.8"></path>',
    'lock' => '<rect x="3.4" y="7" width="9.2" height="6.6" rx="1.5"></rect><path d="M5.6 7V5.2a2.4 2.4 0 0 1 4.8 0V7"></path>',
    'device' => '<rect x="5" y="2.2" width="6" height="11.6" rx="1.5"></rect><line x1="7.2" y1="11.6" x2="8.8" y2="11.6"></line>',
    'info' => '<circle cx="8" cy="8" r="5.6"></circle><line x1="8" y1="7.2" x2="8" y2="11"></line><circle cx="8" cy="5" r="0.5" fill="currentColor" stroke="none"></circle>',
    'check' => '<path d="M3.2 8.4 6.3 11.5 12.8 4.8"></path>',
    'check-circle' => '<circle cx="8" cy="8" r="5.6"></circle><path d="m5.4 8.2 1.9 1.9 3.4-3.9"></path>',
    'close' => '<line x1="4" y1="4" x2="12" y2="12"></line><line x1="12" y1="4" x2="4" y2="12"></line>',
    'menu' => '<line x1="2.4" y1="4.4" x2="13.6" y2="4.4"></line><line x1="2.4" y1="8" x2="13.6" y2="8"></line><line x1="2.4" y1="11.6" x2="13.6" y2="11.6"></line>',
    'chevron-down' => '<path d="M4.4 6.4 8 10l3.6-3.6"></path>',
    'chevron-right' => '<path d="M6.4 4.4 10 8l-3.6 3.6"></path>',
    'arrow-right' => '<line x1="2.8" y1="8" x2="12.8" y2="8"></line><path d="M9.2 4.4 12.8 8l-3.6 3.6"></path>',
    'sun' => '<circle cx="8" cy="8" r="3"></circle><line x1="8" y1="1.6" x2="8" y2="3"></line><line x1="8" y1="13" x2="8" y2="14.4"></line><line x1="1.6" y1="8" x2="3" y2="8"></line><line x1="13" y1="8" x2="14.4" y2="8"></line>',
    'moon' => '<path d="M13 9.6A5.6 5.6 0 0 1 6.4 3a5.6 5.6 0 1 0 6.6 6.6"></path>',
  ];

  $glyph = $paths[$name] ?? $paths['profile'];
@endphp

<svg
  width="{{ $size }}"
  height="{{ $size }}"
  viewBox="0 0 16 16"
  fill="none"
  stroke="currentColor"
  stroke-width="{{ $strokeWidth }}"
  stroke-linecap="round"
  stroke-linejoin="round"
  @if ($label)
    role="img"
    aria-label="{{ $label }}"
  @else
    aria-hidden="true"
  @endif
  {{ $attributes->class(['block shrink-0']) }}
>@if ($label)<title>{{ $label }}</title>@endif{!! $glyph !!}</svg>
