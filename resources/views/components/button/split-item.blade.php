{{-- One line of the menu a <x-button.split> opens: a glyph, then what it does. --}}
{{--
  @var string $href
  @var string $icon
--}}
@props([
  'href',
  'icon',
])

<a
  href="{{ $href }}"
  role="menuitem"
  {{ $attributes->class(['flex items-center gap-2.5 rounded-sm px-2.5 py-2.25 text-base text-ink no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset']) }}
>
  <x-icon :name="$icon" :size="16" :stroke-width="1.6" class="text-muted" />

  <span class="min-w-0 flex-1 truncate">{{ $slot }}</span>
</a>
