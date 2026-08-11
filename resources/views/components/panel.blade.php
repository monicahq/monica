{{--
  A bounded unit of content, with a header that names it and an optional mark in
  the colour of whatever kind of thing it holds.

  Not every section wants one. A heading and 32px of space is the first tool for
  separating two things; a panel is the second, and it is for a section that is
  genuinely independent of what sits above it. Wrapping every paragraph in one
  turns a page into a stack of boxes.

  `flush` is for a panel whose body is a list of rows: the rows draw their own
  padding and their own hairlines, so the body must not add any of its own or every
  row ends up inset from the edge it should be touching.

  A list that grows in place puts its own wrapper inside the body and aims ajax at
  that, rather than at the panel: appending to the panel would append a second body
  rather than more rows.

  @var string|null $title
  @var string|null $subtitle
  @var string|null $icon
  @var string $iconColor
  @var string $iconBackground
  @var bool $flush
  @var string $variant
  @var \Illuminate\View\ComponentSlot|null $action
  @var \Illuminate\View\ComponentSlot|null $footer
--}}
@props([
  'title' => null,
  'subtitle' => null,
  'icon' => null,
  'iconColor' => 'var(--muted)',
  'iconBackground' => 'var(--sand)',
  'flush' => false,
  'variant' => 'default',
])

<section {{ $attributes->class(['erica-panel', 'erica-panel--'.$variant => $variant !== 'default']) }}>
  @if ($title)
    <header class="erica-panel__head">
      @if ($icon)
        <span class="erica-panel__mark" style="background: {{ $iconBackground }}; color: {{ $iconColor }}">
          <x-icon :name="$icon" :size="17" :stroke-width="1.5" />
        </span>
      @endif

      <div class="min-w-0">
        <h2 class="erica-panel__title">{{ $title }}</h2>

        @if ($subtitle)
          <p class="erica-panel__sub">{{ $subtitle }}</p>
        @endif
      </div>

      @isset($action)
        <div class="erica-panel__action">{{ $action }}</div>
      @endisset
    </header>
  @endif

  <div @class(['erica-panel__body', 'erica-panel__body--flush' => $flush])>
    {{ $slot }}
  </div>

  {{--
    The footer slot passes its own attributes through, so a caller can put an id on
    it and aim ajax at it: a list that loads its next page in place has to replace
    the link that asked for it.
  --}}
  @isset($footer)
    <footer {{ $footer->attributes->class(['erica-panel__foot']) }}>{{ $footer }}</footer>
  @endisset
</section>
