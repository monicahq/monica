{{--
  One line of a list: a mark in the colour of the kind of thing it is, what it is
  called, a sentence saying what it holds, and whatever belongs at the far end.

  Lists are rows separated by hairlines rather than a grid of little cards, because
  a row can be read down a column and a card cannot. The last row in a panel drops
  its rule, so the panel's own border is the only line at the bottom.

  A row with an `href` is the link, all of it, and takes the pointer and focus
  treatment. A row without one is a plain division: it is still drawn, but nothing
  suggests it can be pressed. That is how a destination which has not been built
  yet appears. A link that leads nowhere is worse than a word that does not move.

  `meta` is a short machine-ish value at the end (a count, a state). The `end` slot
  is for anything richer: a chevron, a button, a form.

  @var string|null $title
  @var string|null $description
  @var string|null $icon
  @var string|null $meta
  @var string|null $metaSub
  @var string|null $href
  @var \Illuminate\View\ComponentSlot|null $leading
  @var \Illuminate\View\ComponentSlot|null $end
--}}
@props([
  'title' => null,
  'description' => null,
  'icon' => null,
  'meta' => null,
  'metaSub' => null,
  'href' => null,
])

@php
  $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
  @if ($href) href="{{ $href }}" data-turbo="true" @endif
  {{
    $attributes->class([
      'flex w-full items-center gap-3 border-b border-hairline px-4 py-3.5 text-left font-sans no-underline',
      'transition-[background-color] duration-(--duration-fast) ease-out last:border-b-0',
      'cursor-pointer hover:bg-sand focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring' => (bool) $href,
    ])
  }}
>
  @isset($leading)
    {{ $leading }}
  @endisset

  @if (! isset($leading) && $icon)
    <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-sand text-muted">
      <x-icon :name="$icon" :size="18" :stroke-width="1.5" />
    </span>
  @endif

  <div class="min-w-0 flex-1">
    @if ($title)
      <p class="text-base font-semibold text-ink">{{ $title }}</p>
    @endif

    @if ($description)
      <p class="mt-px text-sm text-pretty text-muted">{{ $description }}</p>
    @endif

    {{ $slot }}
  </div>

  @php
    /* A row given an empty `end` slot must not draw the box for it. */
    $hasEnd = isset($end) && $end->isNotEmpty();
  @endphp

  @if ($meta || $metaSub || $hasEnd)
    <div class="ml-auto flex shrink-0 items-center gap-2.5">
      @if ($meta || $metaSub)
        <div class="text-right">
          @if ($meta)
            <b class="block text-sm font-semibold whitespace-nowrap text-ink">{{ $meta }}</b>
          @endif

          @if ($metaSub)
            <span class="block text-xs whitespace-nowrap text-muted">{{ $metaSub }}</span>
          @endif
        </div>
      @endif

      @if ($hasEnd)
        {{ $end }}
      @endif
    </div>
  @endif
</{{ $tag }}>
