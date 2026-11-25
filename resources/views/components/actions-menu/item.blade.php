{{-- One line of an actions menu: an optional glyph, then what it does. --}}
{{--
  @var string|null $href
  @var bool|null $checked
  @var bool $indented
  @var \Illuminate\View\ComponentSlot|null $icon
--}}
@props([
  'href' => null,
  'checked' => null,
  'indented' => false,
])

@php
  $drivenByTurbo = $href !== null && ! $attributes->has('data-turbo') && $attributes->whereStartsWith('x-target')->isEmpty();
@endphp

<a
  href="{{ $href }}"
  role="{{ $checked === null ? 'menuitem' : 'menuitemradio' }}"
  @if ($checked !== null) aria-checked="{{ $checked ? 'true' : 'false' }}" @endif
  @if ($drivenByTurbo) data-turbo="true" @endif
  {{ $attributes->class(['flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md pr-2.5 text-left font-sans text-base text-ink no-underline transition-colors hover:bg-hover focus-ring-inset', $indented ? 'pl-5.5' : 'pl-2.5']) }}
>
  @isset($icon)
    <span class="shrink-0">{{ $icon }}</span>
  @endisset

  {{ $slot }}
</a>
