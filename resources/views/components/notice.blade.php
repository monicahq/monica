{{--
  An inline message attached to the thing it is about, rather than a banner at the
  top of a screen saying something about somewhere else.

  The glyph carries the tone alongside the tint, so a notice still reads as a
  warning rather than as decoration for somebody who cannot see the colour. `quiet`
  is the one with nothing to say about tone: an aside that is neither good news nor
  bad, which is what most of them are.

  @var string $tone
  @var string|null $title
  @var string|null $icon
  @var \Illuminate\View\ComponentSlot|null $action
--}}
@props([
  'tone' => 'quiet',
  'title' => null,
  'icon' => null,
])

@php
  $glyphs = [
    'info' => 'info',
    'success' => 'check-circle',
    'warning' => 'reminder',
    'danger' => 'info',
    'quiet' => 'info',
  ];

  /*
   * The ground and the edge are the tone's own tint and edge, so a notice reads as
   * one object whichever tone it carries. The glyph beside the text is what actually
   * says which tone it is, and it says it without the colour, which is what lets the
   * two tones that have no colour left tell themselves apart by their ground.
   */
  $tints = [
    'info' => 'bg-canvas border-hairline',
    'success' => 'bg-success-tint border-success-edge',
    'warning' => 'bg-warning-tint border-warning-edge',
    'danger' => 'bg-error-tint border-error-edge',
    'quiet' => 'bg-card border-hairline',
  ];

  $hues = [
    'info' => 'text-body',
    'success' => 'text-success',
    'warning' => 'text-warning',
    'danger' => 'text-error',
    'quiet' => 'text-muted',
  ];
@endphp

<div {{ $attributes->class(['flex items-start gap-2.75 rounded-md border px-4 py-3.5 font-sans', $tints[$tone]]) }}>
  <x-icon
    :name="$icon ?? $glyphs[$tone]"
    :size="18"
    :stroke-width="1.5"
    class="mt-px {{ $hues[$tone] }}"
  />

  <div class="min-w-0">
    @if ($title)
      <p class="text-base font-semibold text-ink">{{ $title }}</p>
    @endif

    <p class="mt-[3px] text-sm text-pretty text-body">{{ $slot }}</p>
  </div>

  @isset($action)
    <div class="ml-auto shrink-0">{{ $action }}</div>
  @endisset
</div>
