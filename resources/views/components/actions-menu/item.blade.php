{{-- One line of an actions menu: an optional glyph, then what it does. --}}
{{--
  @var string|null $href
  @var \Illuminate\View\ComponentSlot|null $icon
--}}
@props([
  'href' => null,
])

<a
  href="{{ $href }}"
  role="menuitem"
  {{ $attributes->class(['flex w-full cursor-pointer items-center gap-2.5 rounded-xs px-2.5 py-2 text-left font-sans text-sm text-ink no-underline transition-colors hover:bg-hover focus-ring-inset']) }}
>
  @isset($icon)
    <span class="shrink-0">{{ $icon }}</span>
  @endisset

  {{ $slot }}
</a>
