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
   * The border is the tint mixed back towards its own hue, so the edge is a shade
   * of the fill rather than the grey hairline the rest of the system uses.
   */
  $tints = [
    'info' => 'bg-indigo-tint border-[color-mix(in_oklab,var(--indigo)_22%,var(--indigo-tint))]',
    'success' => 'bg-forest-tint border-[color-mix(in_oklab,var(--forest)_22%,var(--forest-tint))]',
    'warning' => 'bg-marigold-tint border-[color-mix(in_oklab,var(--marigold)_30%,var(--marigold-tint))]',
    'danger' => 'bg-error-tint border-[color-mix(in_oklab,var(--danger)_22%,var(--danger-tint))]',
    'quiet' => 'bg-sand border-hairline',
  ];

  $hues = [
    'info' => 'text-indigo-deep',
    'success' => 'text-forest-deep',
    'warning' => 'text-marigold-deep',
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
