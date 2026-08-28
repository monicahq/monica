{{--
  The light and dark switch. It writes to the Alpine theme store, which puts the
  class on <html> and remembers the choice.

  Which glyph shows is decided in css rather than by a binding, because the inline
  script in partials/meta has already put the class on <html> before the first
  paint, while Alpine only starts once the page is on the screen. A bound icon would
  show the wrong one for that gap.

  For the same reason the name it carries covers both states rather than naming the
  one it would switch to: a label that has to be corrected after paint is a label a
  screen reader may have already read out.

  `tone="shell"` is for the one in the head of the navigation column. That column is
  dark chrome whichever theme is on, so the switch in it cannot take its colours from
  the theme the way the rest of the interface does.

  @var string $tone
--}}
@props([
  'tone' => 'default',
])

@php
  $colours = $tone === 'shell'
    ? 'text-shell-muted hover:bg-shell-hover hover:text-shell-ink'
    : 'text-muted hover:bg-sand hover:text-ink';
@endphp

<button
  x-data
  type="button"
  @click="$store.theme.toggle()"
  aria-label="{{ __('Switch between the light and dark theme') }}"
  title="{{ __('Switch between the light and dark theme') }}"
  {{--
    The square after it widens what a thumb has to hit without widening what is
    drawn.
  --}}
  {{
    $attributes->class([
      'relative inline-flex size-8.5 cursor-pointer items-center justify-center rounded-md',
      $colours,
      "after:absolute after:-inset-1.5 after:content-['']",
      'transition-[background-color,color,transform] duration-(--duration-fast) ease-out',
      'active:translate-y-(--press-translate)',
      'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
    ])
  }}
>
  <x-icon name="sun" :size="17" :stroke-width="1.5" class="dark:hidden" />
  <x-icon name="moon" :size="17" :stroke-width="1.5" class="hidden dark:block" />
</button>
