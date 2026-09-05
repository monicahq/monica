{{-- The glyph an entity type is marked with, drawn from the name the type stores. --}}
{{--
  @var string|null $name
  @var int $size
--}}
@props([
  'name' => null,
  'size' => 15,
])

@php
  $paths = [
    'profile' => '<circle cx="8" cy="6" r="2.6"></circle><path d="M3 14c0-2.6 2.2-4 5-4s5 1.4 5 4"></path>',
    'logs' => '<path d="M4 2.4h5.2L12.4 5.6V13.6H4z"></path><line x1="6.2" y1="8" x2="10.2" y2="8"></line><line x1="6.2" y1="10.6" x2="10.2" y2="10.6"></line>',
    'security' => '<rect x="3.4" y="7" width="9.2" height="6.6" rx="1.5"></rect><path d="M5.6 7V5.2a2.4 2.4 0 0 1 4.8 0V7"></path>',
    'preferences' => '<rect x="2.4" y="3.2" width="11.2" height="9.6" rx="1.5"></rect><line x1="2.4" y1="6.4" x2="13.6" y2="6.4"></line>',
    'key' => '<circle cx="10.4" cy="5.6" r="3"></circle><path d="M8.3 7.7 3 13"></path><path d="M4.6 11.4 6 12.8"></path>',
    'device' => '<rect x="5" y="2.2" width="6" height="11.6" rx="1.5"></rect><line x1="7.2" y1="11.6" x2="8.8" y2="11.6"></line>',
    'info' => '<circle cx="8" cy="8" r="5.6"></circle><line x1="8" y1="7.2" x2="8" y2="11"></line><circle cx="8" cy="5" r="0.5" fill="currentColor" stroke="none"></circle>',
    'activity' => '<path d="M2.2 8.6h3L6.8 4l2.4 8 1.6-3.4h3"></path>',
    'mail' => '<rect x="2.2" y="3.8" width="11.6" height="8.4" rx="1.5"></rect><path d="m2.6 4.8 5.4 3.8 5.4-3.8"></path>',
    'sun' => '<circle cx="8" cy="8" r="3"></circle><line x1="8" y1="1.6" x2="8" y2="3"></line><line x1="8" y1="13" x2="8" y2="14.4"></line><line x1="1.6" y1="8" x2="3" y2="8"></line><line x1="13" y1="8" x2="14.4" y2="8"></line>',
    'moon' => '<path d="M13 9.6A5.6 5.6 0 0 1 6.4 3a5.6 5.6 0 1 0 6.6 6.6"></path>',
    'check' => '<circle cx="8" cy="8" r="5.6"></circle><path d="m5.4 8.2 1.9 1.9 3.4-3.9"></path>',
  ];

  $glyph = $paths[$name] ?? $paths[\App\Models\EntityType::DEFAULT_ICON];
@endphp

<svg
  width="{{ $size }}"
  height="{{ $size }}"
  viewBox="0 0 16 16"
  fill="none"
  stroke="currentColor"
  stroke-width="1.4"
  stroke-linecap="round"
  stroke-linejoin="round"
  aria-hidden="true"
  {{ $attributes->class(['shrink-0']) }}
>{!! $glyph !!}</svg>
