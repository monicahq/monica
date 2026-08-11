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
  @var string $iconColor
  @var string $iconBackground
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
  'iconColor' => 'var(--muted)',
  'iconBackground' => 'var(--sand)',
  'meta' => null,
  'metaSub' => null,
  'href' => null,
])

@php
  $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
  @if ($href) href="{{ $href }}" data-turbo="true" @endif
  {{ $attributes->class(['erica-row', 'erica-row--interactive' => (bool) $href]) }}
>
  @isset($leading)
    {{ $leading }}
  @endisset

  @if (! isset($leading) && $icon)
    <span class="erica-row__mark" style="background: {{ $iconBackground }}; color: {{ $iconColor }}">
      <x-icon :name="$icon" :size="18" :stroke-width="1.5" />
    </span>
  @endif

  <div class="erica-row__main">
    @if ($title)
      <p class="erica-row__title">{{ $title }}</p>
    @endif

    @if ($description)
      <p class="erica-row__desc">{{ $description }}</p>
    @endif

    {{ $slot }}
  </div>

  @php
    /* A row given an empty `end` slot must not draw the box for it. */
    $hasEnd = isset($end) && $end->isNotEmpty();
  @endphp

  @if ($meta || $metaSub || $hasEnd)
    <div class="erica-row__end">
      @if ($meta || $metaSub)
        <div class="erica-row__meta">
          @if ($meta)
            <b>{{ $meta }}</b>
          @endif

          @if ($metaSub)
            <span>{{ $metaSub }}</span>
          @endif
        </div>
      @endif

      @if ($hasEnd)
        {{ $end }}
      @endif
    </div>
  @endif
</{{ $tag }}>
