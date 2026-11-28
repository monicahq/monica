{{-- One glyph of the icon language. --}}
{{--
  @var string $name
  @var int $size
  @var float $strokeWidth
  @var string|null $label
  @var bool $mirror
--}}
@props([
  'name' => 'profile',
  'size' => 18,
  'strokeWidth' => 1.4,
  'label' => null,
  'mirror' => true,
])

@php
  $paths = [
    'profile' => '<circle cx="8" cy="6" r="2.6"></circle><path d="M3 14c0-2.6 2.2-4 5-4s5 1.4 5 4"></path>',
    'people' => '<circle cx="6" cy="6.2" r="2.3"></circle><path d="M1.9 13.4c0-2.3 1.9-3.6 4.1-3.6s4.1 1.3 4.1 3.6"></path><path d="M11 4.3a2.2 2.2 0 0 1 0 4.2"></path><path d="M11.7 10.2c1.6.3 2.8 1.4 2.8 3.2"></path>',
    'relationship' => '<circle cx="4.4" cy="5.4" r="2.2"></circle><circle cx="11.6" cy="10.6" r="2.2"></circle><path d="M6.3 7.1 9.7 8.9"></path>',
    'timeline' => '<line x1="4" y1="2.6" x2="4" y2="13.4"></line><circle cx="4" cy="5.2" r="1.4"></circle><circle cx="4" cy="10.8" r="1.4"></circle><line x1="7.2" y1="5.2" x2="13.4" y2="5.2"></line><line x1="7.2" y1="10.8" x2="12" y2="10.8"></line>',
    'memory' => '<rect x="2.2" y="3.4" width="11.6" height="9.2" rx="1.6"></rect><path d="m3.5 11.3 2.9-3.1 2.3 2.2 2-1.8 2 2.2"></path><circle cx="10.4" cy="6.4" r="1"></circle>',
    'note' => '<path d="M4 2.4h5.2L12.4 5.6V13.6H4z"></path><line x1="6.2" y1="8" x2="10.2" y2="8"></line><line x1="6.2" y1="10.6" x2="10.2" y2="10.6"></line>',
    'folder' => '<path d="M2 4.5A1.5 1.5 0 0 1 3.5 3h2.6l1.5 1.6h4.9A1.5 1.5 0 0 1 14 6.1v5.4a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 11.5z"></path>',
    'reminder' => '<path d="M4.4 11.2V7.6a3.6 3.6 0 0 1 7.2 0v3.6"></path><line x1="3" y1="11.2" x2="13" y2="11.2"></line><path d="M6.8 13.1a1.6 1.6 0 0 0 2.4 0"></path>',
    'activity' => '<path d="M2.2 8.6h3L6.8 4l2.4 8 1.6-3.4h3"></path>',
    'place' => '<path d="M8 14c2.6-3 4-5.1 4-7A4 4 0 0 0 4 7c0 1.9 1.4 4 4 7Z"></path><circle cx="8" cy="6.9" r="1.5"></circle>',
    'pet' => '<ellipse cx="4.9" cy="5.6" rx="1.3" ry="1.7"></ellipse><ellipse cx="8.6" cy="4.4" rx="1.3" ry="1.8"></ellipse><ellipse cx="12" cy="6.3" rx="1.3" ry="1.6"></ellipse><path d="M8.2 8.4c2 0 3.6 1.4 3.6 3 0 1.2-1 1.9-2.2 1.6-1-.3-1.8-.3-2.8 0-1.2.3-2.2-.4-2.2-1.6 0-1.6 1.6-3 3.6-3Z"></path>',
    'organization' => '<path d="M3 13.4V3.6a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v9.8"></path><path d="M10 6.6h2.9a1 1 0 0 1 1 1v5.8"></path><line x1="5.2" y1="5.4" x2="7.8" y2="5.4"></line><line x1="5.2" y1="8" x2="7.8" y2="8"></line><line x1="1.8" y1="13.4" x2="14.2" y2="13.4"></line>',
    'gift' => '<rect x="2.4" y="6.6" width="11.2" height="6.8" rx="1.2"></rect><line x1="1.8" y1="6.6" x2="14.2" y2="6.6"></line><line x1="8" y1="6.6" x2="8" y2="13.4"></line><path d="M8 6.6C6.6 4.6 5.6 2.8 4.2 3.4 2.9 4 3.6 6.2 8 6.6Z"></path><path d="M8 6.6c1.4-2 2.4-3.8 3.8-3.2 1.3.6.6 2.8-3.8 3.2Z"></path>',
    'family' => '<path d="M2.6 7.4 8 3.2l5.4 4.2"></path><path d="M4 8.6v4.8h8V8.6"></path><circle cx="6.4" cy="10.8" r="1"></circle><circle cx="9.6" cy="10.8" r="1"></circle>',
    'journal' => '<path d="M2.4 3.4c2.2 0 4 .5 5.6 1.6 1.6-1.1 3.4-1.6 5.6-1.6v8.8c-2.2 0-4 .5-5.6 1.6-1.6-1.1-3.4-1.6-5.6-1.6Z"></path><path d="M8 5v8.6"></path>',
    'tasks' => '<path d="M2.6 5.2 4 6.6l2.4-2.6"></path><path d="M2.6 11 4 12.4l2.4-2.6"></path><line x1="8.6" y1="5.6" x2="13.6" y2="5.6"></line><line x1="8.6" y1="11.4" x2="13.6" y2="11.4"></line>',
    'vault' => '<rect x="2.2" y="3" width="11.6" height="10" rx="1.6"></rect><circle cx="7.4" cy="8" r="2.4"></circle><line x1="11.8" y1="6.2" x2="11.8" y2="9.8"></line>',
    'file' => '<path d="M4 2.4h5.2L12.4 5.6V13.6H4z"></path><path d="M9.2 2.4v3.2h3.2"></path>',
    'settings' => '<path d="M6.81 3.56L6.87 1.9L9.13 1.9L9.19 3.56L11.25 4.75L12.71 3.97L13.84 5.93L12.44 6.81L12.44 9.19L13.84 10.07L12.71 12.03L11.25 11.25L9.19 12.44L9.13 14.1L6.87 14.1L6.81 12.44L4.75 11.25L3.29 12.03L2.16 10.07L3.56 9.19L3.56 6.81L2.16 5.93L3.29 3.97L4.75 4.75Z"></path><circle cx="8" cy="8" r="2.1"></circle>',
    'calendar' => '<rect x="2.4" y="3.4" width="11.2" height="10.2" rx="1.5"></rect><line x1="2.4" y1="6.4" x2="13.6" y2="6.4"></line><line x1="5.4" y1="2.2" x2="5.4" y2="4.2"></line><line x1="10.6" y1="2.2" x2="10.6" y2="4.2"></line>',
    'search' => '<circle cx="7.2" cy="7.2" r="4.2"></circle><line x1="10.4" y1="10.4" x2="13.6" y2="13.6"></line>',
    'plus' => '<line x1="8" y1="3.2" x2="8" y2="12.8"></line><line x1="3.2" y1="8" x2="12.8" y2="8"></line>',
    'heart' => '<path d="M8 13.2C4.6 11 2.4 9.1 2.4 6.6A2.9 2.9 0 0 1 8 5.2a2.9 2.9 0 0 1 5.6 1.4c0 2.5-2.2 4.4-5.6 6.6Z"></path>',
    'star' => '<path d="M8 2.4 9.8 6l4 .6-2.9 2.8.7 4L8 11.5 4.4 13.4l.7-4L2.2 6.6l4-.6z"></path>',
    'star-filled' => '<path fill="currentColor" d="M8 2.4 9.8 6l4 .6-2.9 2.8.7 4L8 11.5 4.4 13.4l.7-4L2.2 6.6l4-.6z"></path>',
    'mail' => '<rect x="2.2" y="3.8" width="11.6" height="8.4" rx="1.5"></rect><path d="m2.6 4.8 5.4 3.8 5.4-3.8"></path>',
    'key' => '<circle cx="10.4" cy="5.6" r="3"></circle><path d="M8.3 7.7 3 13"></path><path d="M4.6 11.4 6 12.8"></path>',
    'link' => '<path d="M6.6 9.4a2.4 2.4 0 0 1 0-3.4l2-2a2.4 2.4 0 0 1 3.4 3.4l-.9.9"></path><path d="M9.4 6.6a2.4 2.4 0 0 1 0 3.4l-2 2a2.4 2.4 0 0 1-3.4-3.4l.9-.9"></path>',
    'lock' => '<rect x="3.4" y="7" width="9.2" height="6.6" rx="1.5"></rect><path d="M5.6 7V5.2a2.4 2.4 0 0 1 4.8 0V7"></path>',
    'device' => '<rect x="5" y="2.2" width="6" height="11.6" rx="1.5"></rect><line x1="7.2" y1="11.6" x2="8.8" y2="11.6"></line>',
    'info' => '<circle cx="8" cy="8" r="5.6"></circle><line x1="8" y1="7.2" x2="8" y2="11"></line><circle cx="8" cy="5" r="0.5" fill="currentColor" stroke="none"></circle>',
    'warning' => '<path d="M8 2.4 14 13H2z"></path><line x1="8" y1="6.6" x2="8" y2="9.4"></line><circle cx="8" cy="11.2" r="0.5" fill="currentColor" stroke="none"></circle>',
    'check' => '<path d="M3.2 8.4 6.3 11.5 12.8 4.8"></path>',
    'check-circle' => '<circle cx="8" cy="8" r="5.6"></circle><path d="m5.4 8.2 1.9 1.9 3.4-3.9"></path>',
    'close' => '<line x1="4" y1="4" x2="12" y2="12"></line><line x1="12" y1="4" x2="4" y2="12"></line>',
    'menu' => '<line x1="2.4" y1="4.4" x2="13.6" y2="4.4"></line><line x1="2.4" y1="8" x2="13.6" y2="8"></line><line x1="2.4" y1="11.6" x2="13.6" y2="11.6"></line>',
    'chevron-up' => '<path d="M4.4 9.6 8 6l3.6 3.6"></path>',
    'chevron-down' => '<path d="M4.4 6.4 8 10l3.6-3.6"></path>',
    'chevron-right' => '<path d="M6.4 4.4 10 8l-3.6 3.6"></path>',
    'chevron-left' => '<path d="M9.6 4.4 6 8l3.6 3.6"></path>',
    'thumbs-up' => '<path d="M5 7.2v6.2H2.8V7.2z"></path><path d="M5 7.2 7.6 2.6c.9 0 1.6.8 1.4 1.7L8.6 6.4h3.6c.9 0 1.5.8 1.3 1.6l-1 4.2c-.2.7-.8 1.2-1.5 1.2H5"></path>',
    'comment' => '<path d="M3 3.5h10a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H7l-3 2.5v-2.5H3a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1z"></path>',
    'arrow-right' => '<line x1="2.8" y1="8" x2="12.8" y2="8"></line><path d="M9.2 4.4 12.8 8l-3.6 3.6"></path>',
    'reload' => '<path d="M13.2 8a5.2 5.2 0 1 1-1.52-3.68"></path><path d="M11.7 1.8v2.5H9.2"></path>',
    'enter' => '<path d="M12.8 3.2v4.4a1.6 1.6 0 0 1-1.6 1.6H3.6"></path><path d="M6.4 6.4 3.6 9.2 6.4 12"></path>',
    'sun' => '<circle cx="8" cy="8" r="3"></circle><line x1="8" y1="1.6" x2="8" y2="3"></line><line x1="8" y1="13" x2="8" y2="14.4"></line><line x1="1.6" y1="8" x2="3" y2="8"></line><line x1="13" y1="8" x2="14.4" y2="8"></line>',
    'moon' => '<path d="M13 9.6A5.6 5.6 0 0 1 6.4 3a5.6 5.6 0 1 0 6.6 6.6"></path>',
    'grip' => '<circle cx="6" cy="4" r="1" fill="currentColor" stroke="none"></circle><circle cx="10" cy="4" r="1" fill="currentColor" stroke="none"></circle><circle cx="6" cy="8" r="1" fill="currentColor" stroke="none"></circle><circle cx="10" cy="8" r="1" fill="currentColor" stroke="none"></circle><circle cx="6" cy="12" r="1" fill="currentColor" stroke="none"></circle><circle cx="10" cy="12" r="1" fill="currentColor" stroke="none"></circle>',
    'text' => '<rect x="2.4" y="5.4" width="11.2" height="5.2" rx="1.5"></rect><line x1="4.8" y1="8" x2="8.4" y2="8"></line>',
    'text-lines' => '<rect x="2.4" y="3.4" width="11.2" height="9.2" rx="1.5"></rect><line x1="4.6" y1="6.4" x2="11.4" y2="6.4"></line><line x1="4.6" y1="8.6" x2="11.4" y2="8.6"></line><line x1="4.6" y1="10.8" x2="8.6" y2="10.8"></line>',
    'hash' => '<line x1="6.4" y1="2.8" x2="5" y2="13.2"></line><line x1="11" y1="2.8" x2="9.6" y2="13.2"></line><line x1="2.8" y1="6.2" x2="13.2" y2="6.2"></line><line x1="2.4" y1="9.8" x2="12.8" y2="9.8"></line>',
    'radio' => '<circle cx="4.6" cy="5" r="2.2"></circle><circle cx="4.6" cy="5" r="0.9" fill="currentColor" stroke="none"></circle><circle cx="4.6" cy="11" r="2.2"></circle><line x1="8.6" y1="5" x2="13.4" y2="5"></line><line x1="8.6" y1="11" x2="13.4" y2="11"></line>',
    'tag' => '<path d="M13.4 8.9 8.9 13.4a1.2 1.2 0 0 1-1.7 0L2.6 8.8V2.6h6.2l4.6 4.6a1.2 1.2 0 0 1 0 1.7Z"></path><circle cx="5.6" cy="5.6" r="1"></circle>',
    'calendar-repeat' => '<rect x="2.4" y="3.4" width="11.2" height="10.2" rx="1.5"></rect><line x1="2.4" y1="6.4" x2="13.6" y2="6.4"></line><line x1="5.4" y1="2.2" x2="5.4" y2="4.2"></line><line x1="10.6" y1="2.2" x2="10.6" y2="4.2"></line><path d="M5.6 11.4a2.5 2.5 0 0 1 4.2-1.8"></path><path d="M10.1 8.2v1.7H8.4"></path>',
    'clock' => '<circle cx="8" cy="8" r="5.6"></circle><path d="M8 4.9V8l2.4 1.6"></path>',
    'alarm' => '<circle cx="8" cy="9.2" r="4.4"></circle><path d="M8 6.6V9.2l1.9 1.3"></path><path d="M3.7 4.4 5.3 3"></path><path d="M12.3 4.4 10.7 3"></path>',
    'bell' => '<path d="M4.2 10.8V7.4a3.8 3.8 0 0 1 7.6 0v3.4l1 1.2H3.2Z"></path><path d="M6.6 13.6a1.6 1.6 0 0 0 2.8 0"></path>',
    'shop' => '<path d="M2.6 6.2 3.6 2.8h8.8l1 3.4"></path><path d="M2.6 6.2c0 .9.8 1.6 1.8 1.6s1.8-.7 1.8-1.6c0 .9.8 1.6 1.8 1.6s1.8-.7 1.8-1.6c0 .9.8 1.6 1.8 1.6s1.8-.7 1.8-1.6"></path><path d="M3.4 7.8v5.6h9.2V7.8"></path><path d="M6.6 13.4v-3h2.8v3"></path>',
    'shield' => '<path d="M8 2.2 13 4v3.8c0 3-2.2 5-5 6-2.8-1-5-3-5-6V4Z"></path><path d="m5.8 8 1.5 1.5 3-3"></path>',
    'layers' => '<rect x="2.4" y="2.6" width="11.2" height="4.6" rx="1.4"></rect><rect x="2.4" y="8.8" width="11.2" height="4.6" rx="1.4"></rect><line x1="4.8" y1="4.9" x2="8.4" y2="4.9"></line><line x1="4.8" y1="11.1" x2="8.4" y2="11.1"></line>',
    'coin' => '<rect x="2" y="4.6" width="12" height="6.8" rx="1.4"></rect><circle cx="8" cy="8" r="1.8"></circle>',
    'divider' => '<rect x="2.4" y="2.6" width="11.2" height="3.2" rx="1"></rect><line x1="1.8" y1="8" x2="14.2" y2="8"></line><rect x="2.4" y="10.2" width="11.2" height="3.2" rx="1"></rect>',
    'heading' => '<line x1="3" y1="4.8" x2="9.6" y2="4.8"></line><line x1="3" y1="8.4" x2="13" y2="8.4"></line><line x1="3" y1="11.6" x2="13" y2="11.6"></line>',
    'swatch' => '<circle cx="8" cy="8" r="5.6"></circle><path d="M8 2.4a5.6 5.6 0 0 1 0 11.2Z" fill="currentColor" stroke="none"></path>',

    'home' => '<path d="M2.6 7.4 8 3.2l5.4 4.2"></path><path d="M4 8.6v4.8h8V8.6"></path><path d="M6.8 13.4V10h2.4v3.4"></path>',
    'health' => '<path d="M6.5 2.6h3v3.9h3.9v3h-3.9v3.9h-3V9.5H2.6v-3h3.9z"></path>',
    'briefcase' => '<rect x="2.2" y="4.8" width="11.6" height="8.4" rx="1.5"></rect><path d="M6 4.8V3.6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1.2"></path><line x1="2.2" y1="8.4" x2="13.8" y2="8.4"></line>',
    'graduation' => '<path d="M1.8 6.4 8 3.4l6.2 3-6.2 3z"></path><path d="M4.6 7.8v3.3c0 1 1.5 1.8 3.4 1.8s3.4-.8 3.4-1.8V7.8"></path>',
    'car' => '<path d="M2.4 10.6V8.2l1.4-3.2a1.2 1.2 0 0 1 1.1-.7h6.2a1.2 1.2 0 0 1 1.1.7l1.4 3.2v2.4"></path><line x1="2.6" y1="8.2" x2="13.4" y2="8.2"></line><circle cx="4.9" cy="10.9" r="1.2"></circle><circle cx="11.1" cy="10.9" r="1.2"></circle>',
    'plane' => '<path d="M13.6 2.6 2.4 7.1l4.3 1.6 1.5 4.4z"></path><path d="M6.7 8.7 13.6 2.6"></path>',
    'globe' => '<circle cx="8" cy="8" r="5.6"></circle><ellipse cx="8" cy="8" rx="2.4" ry="5.6"></ellipse><line x1="2.7" y1="6.2" x2="13.3" y2="6.2"></line><line x1="2.7" y1="9.8" x2="13.3" y2="9.8"></line>',
    'food' => '<path d="M4.6 2.6v3.3a1.5 1.5 0 0 1-3 0V2.6"></path><line x1="3.1" y1="6.4" x2="3.1" y2="13.4"></line><path d="M10.4 2.6c1.7 1.3 2.6 3 2.6 4.9h-2.6z"></path><line x1="11.7" y1="7.5" x2="11.7" y2="13.4"></line>',
    'music' => '<circle cx="4.4" cy="11.4" r="1.9"></circle><circle cx="11.4" cy="10.2" r="1.9"></circle><line x1="6.3" y1="11.4" x2="6.3" y2="4.4"></line><line x1="13.3" y1="10.2" x2="13.3" y2="3.2"></line><path d="M6.3 4.4 13.3 3.2"></path>',
    'ball' => '<circle cx="8" cy="8" r="5.6"></circle><path d="M4 4a7.6 7.6 0 0 1 0 8"></path><path d="M12 4a7.6 7.6 0 0 0 0 8"></path>',
    'book' => '<path d="M3.4 2.6h7.2a2 2 0 0 1 2 2v8.8H5.4a2 2 0 0 1-2-2z"></path><line x1="5.4" y1="11.2" x2="12.6" y2="11.2"></line>',
    'phone' => '<path d="M5.2 2.8h1.9l1 2.6-1.4 1a6.4 6.4 0 0 0 2.9 2.9l1-1.4 2.6 1v1.9a1.3 1.3 0 0 1-1.4 1.3A9.5 9.5 0 0 1 3.9 4.2a1.3 1.3 0 0 1 1.3-1.4Z"></path>',
    'target' => '<circle cx="8" cy="8" r="5.6"></circle><circle cx="8" cy="8" r="2.6"></circle><circle cx="8" cy="8" r="0.6" fill="currentColor" stroke="none"></circle>',
    'sparkles' => '<path d="M7.34 1.88a0.67 0.67 0 0 1 1.31 0l0.7 3.71a1.33 1.33 0 0 0 1.06 1.06l3.71 0.7a0.67 0.67 0 0 1 0 1.31l-3.71 0.7a1.33 1.33 0 0 0 -1.06 1.06l-0.7 3.71a0.67 0.67 0 0 1 -1.31 0l-0.7 -3.71a1.33 1.33 0 0 0 -1.06 -1.06l-3.71 -0.7a0.67 0.67 0 0 1 0 -1.31l3.71 -0.7a1.33 1.33 0 0 0 1.06 -1.06z"></path><path d="M13.33 1.33v2.67"></path><path d="M14.67 2.67h-2.67"></path><circle cx="2.67" cy="13.33" r="1.33"></circle>',
    'credit-card' => '<rect x="1.33" y="3.33" width="13.33" height="9.33" rx="1.33"></rect><line x1="1.33" y1="6.67" x2="14.67" y2="6.67"></line>',
    'copy' => '<rect x="5.4" y="5.4" width="8.2" height="8.2" rx="1.5"></rect><path d="M10.6 5.4V3.9a1.5 1.5 0 0 0-1.5-1.5H3.9a1.5 1.5 0 0 0-1.5 1.5v5.2a1.5 1.5 0 0 0 1.5 1.5h1.5"></path>',
    'download' => '<line x1="8" y1="2.6" x2="8" y2="9.8"></line><path d="M5 7 8 10l3-3"></path><path d="M2.8 11.2v1.2a1.2 1.2 0 0 0 1.2 1.2h8a1.2 1.2 0 0 0 1.2-1.2v-1.2"></path>',
    'code' => '<path d="M5.8 4.6 2.6 8l3.2 3.4"></path><path d="M10.2 4.6 13.4 8l-3.2 3.4"></path>',
    'question' => '<circle cx="8" cy="8" r="6.67"></circle><path d="M6.06 6a2 2 0 0 1 3.89 0.67c0 1.33 -2 2 -2 2"></path><path d="M8 11.33h0.01"></path>',
    'bug' => '<path d="M8 13.33v-6"></path><path d="M9.33 4.67a2.67 2.67 0 0 1 2.67 2.67v2a4 4 0 0 1 -8 0v-2a2.67 2.67 0 0 1 2.67 -2.67z"></path><path d="M9.41 2.59 10.67 1.33"></path><path d="M14 14a2.67 2.67 0 0 0 -2.54 -2.67"></path><path d="M14 3.33a2.67 2.67 0 0 1 -2.37 2.65"></path><path d="M14.67 8.67h-2.67"></path><path d="M2 14a2.67 2.67 0 0 1 2.54 -2.67"></path><path d="M2 3.33a2.67 2.67 0 0 0 2.37 2.65"></path><path d="M4 8.67H1.33"></path><path d="m5.33 1.33 1.25 1.25"></path><path d="M6 4.75V4a2 2 0 1 1 4 0v0.75"></path>',
  ];

  $glyph = $paths[$name] ?? $paths['profile'];

  // A glyph that points along the line of reading points the other way when the page reads right to left.
  // A caller that turns the glyph to point up or down passes :mirror="false", since up and down do not flip.
  $mirrored = $mirror && in_array($name, ['chevron-left', 'chevron-right', 'arrow-right'], true);
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
  {{ $attributes->class(['block shrink-0', 'rtl:-scale-x-100' => $mirrored]) }}
>@if ($label)<title>{{ $label }}</title>@endif{!! $glyph !!}</svg>
