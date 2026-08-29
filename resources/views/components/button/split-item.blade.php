{{--
  One line of the menu a <x-button.split> opens: a glyph, then what it does.

  It is an anchor rather than a button because the split button is used inside a
  form, and the menu of one cannot hold a form of its own. An item that has to
  post therefore goes to a screen that asks first, which is what these actions
  want anyway: they are the ones somebody reaches for rather than the one they
  came to press.

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
  {{ $attributes->class(['flex items-center gap-2.5 rounded-sm px-2.5 py-2.25 text-base text-ink no-underline transition-[background-color] duration-(--duration-fast) ease-out hover:bg-sand focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring']) }}
>
  <x-icon :name="$icon" :size="16" :stroke-width="1.6" class="text-muted" />

  <span class="min-w-0 flex-1 truncate">{{ $slot }}</span>
</a>
