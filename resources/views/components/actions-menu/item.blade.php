{{-- One line of an actions menu: an optional glyph, then what it does. --}}
{{--
  @var string|null $href
  @var \Illuminate\View\ComponentSlot|null $icon
--}}
@props([
  'href' => null,
])

@php
  $drivenByTurbo = $href !== null && ! $attributes->has('data-turbo') && $attributes->whereStartsWith('x-target')->isEmpty();
@endphp

<a
  href="{{ $href }}"
  role="menuitem"
  @if ($drivenByTurbo) data-turbo="true" @endif
  {{ $attributes->class(['flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-left font-sans text-base text-ink no-underline transition-colors hover:bg-hover focus-ring-inset']) }}
>
  @isset($icon)
    <span class="shrink-0">{{ $icon }}</span>
  @endisset

  {{ $slot }}
</a>
