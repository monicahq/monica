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

  `iconClass` is the colour of whatever kind of thing the panel holds, written as
  the pair of utilities that carries it: the tint it sits on and the hue it is
  drawn in. Left alone it is neutral.

  @var string|null $title
  @var string|null $subtitle
  @var string|null $icon
  @var string $iconClass
  @var bool $flush
  @var string $variant
  @var \Illuminate\View\ComponentSlot|null $action
  @var \Illuminate\View\ComponentSlot|null $footer
--}}
@props([
  'title' => null,
  'subtitle' => null,
  'icon' => null,
  'iconClass' => 'bg-sand text-muted',
  'flush' => false,
  'variant' => 'default',
])

@php
  /*
   * The surface is chosen rather than layered: `shadow-none` written after
   * `shadow-raised` does not win, because which of two utilities setting the same
   * property applies is decided by the order Tailwind emits them and not by the
   * order they are written here.
   */
  $surface = $variant === 'inset' ? 'bg-sand' : 'bg-canvas shadow-raised';
@endphp

<section {{ $attributes->class(['overflow-hidden rounded-lg border border-hairline', $surface]) }}>
  @if ($title)
    <header class="flex min-h-13 items-center gap-2.5 border-b border-hairline px-4.5 py-3">
      @if ($icon)
        <span class="flex size-7 shrink-0 items-center justify-center rounded-sm {{ $iconClass }}">
          <x-icon :name="$icon" :size="17" :stroke-width="1.5" />
        </span>
      @endif

      <div class="min-w-0">
        <h2 class="text-card-title text-ink">{{ $title }}</h2>

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
    <footer {{ $footer->attributes->class(['border-t border-hairline bg-sand px-4.5 py-3 text-sm']) }}>{{ $footer }}</footer>
  @endisset
</section>
