{{--
  A bounded unit of content, with a header that names it and an optional mark
  beside the name.

  Not every section wants one. A heading and 32px of space is the first tool for
  separating two things; a panel is the second, and it is for a section that is
  genuinely independent of what sits above it. Wrapping every paragraph in one
  turns a page into a stack of boxes.

  The header sits on a band and the footer on a slightly deeper one, so a panel
  reads as a named thing with a body between two ends rather than as a box with a
  rule across it. The single pixel underneath is not elevation: it is the border
  given a little weight now that the page behind it is a darker stone.

  `flush` is for a panel whose body is a list of rows: the rows draw their own
  padding and their own hairlines, so the body must not add any of its own or every
  row ends up inset from the edge it should be touching.

  A list that grows in place puts its own wrapper inside the body and aims ajax at
  that, rather than at the panel: appending to the panel would append a second body
  rather than more rows.

  `tone="danger"` is for the panel that ends something. It takes a red edge, a red
  band and a red mark, so the part of a screen that cannot be undone is visible
  before it is read, and it is the only panel on a screen entitled to it.

  @var string|null $title
  @var string|null $subtitle
  @var string|null $icon
  @var bool $flush
  @var string $variant
  @var string $tone
  @var \Illuminate\View\ComponentSlot|null $action
  @var \Illuminate\View\ComponentSlot|null $footer
--}}
@props([
  'title' => null,
  'subtitle' => null,
  'icon' => null,
  'flush' => false,
  'variant' => 'default',
  'tone' => 'default',
])

@php
  $danger = $tone === 'danger';

  $surface = $variant === 'inset' ? 'bg-sand' : 'bg-canvas';
  $edge = $danger ? 'border-error-edge' : 'border-hairline';
  $band = $danger ? 'border-error-edge bg-error-tint' : 'border-hairline-soft bg-sunken';
  $mark = $danger ? 'bg-error text-white' : 'bg-primary text-on-primary';
@endphp

<section {{ $attributes->class(['overflow-hidden rounded-xl border shadow-panel', $edge, $surface]) }}>
  @if ($title)
    <header class="flex min-h-13 items-start gap-3 border-b px-5 py-4 {{ $band }}">
      @if ($icon)
        <span class="flex size-7 shrink-0 items-center justify-center rounded-md {{ $mark }}">
          <x-icon :name="$icon" :size="15" :stroke-width="1.5" />
        </span>
      @endif

      <div class="min-w-0">
        <h2 class="text-card-title {{ $danger ? 'text-error' : 'text-ink' }}">{{ $title }}</h2>

        @if ($subtitle)
          <p class="mt-0.5 text-xs text-muted">{{ $subtitle }}</p>
        @endif
      </div>

      @isset($action)
        <div class="ml-auto flex items-center gap-2">{{ $action }}</div>
      @endisset
    </header>
  @endif

  <div @class(['p-5' => ! $flush])>
    {{ $slot }}
  </div>

  {{--
    The footer slot passes its own attributes through, so a caller can put an id on
    it and aim ajax at it: a list that loads its next page in place has to replace
    the link that asked for it.
  --}}
  @isset($footer)
    <footer {{ $footer->attributes->class(['border-t border-hairline-soft bg-sand px-5 py-3.5 text-sm']) }}>{{ $footer }}</footer>
  @endisset
</section>
