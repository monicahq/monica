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

  $hues = [
    'info' => 'var(--indigo-deep)',
    'success' => 'var(--forest-deep)',
    'warning' => 'var(--marigold-deep)',
    'danger' => 'var(--danger)',
    'quiet' => 'var(--muted)',
  ];
@endphp

<div {{ $attributes->class(['erica-notice', 'erica-notice--'.$tone]) }}>
  <x-icon
    :name="$icon ?? $glyphs[$tone]"
    :size="18"
    :stroke-width="1.5"
    class="mt-px"
    :style="'color: '.$hues[$tone]"
  />

  <div class="min-w-0">
    @if ($title)
      <p class="erica-notice__title">{{ $title }}</p>
    @endif

    <p class="erica-notice__body">{{ $slot }}</p>
  </div>

  @isset($action)
    <div class="erica-notice__end">{{ $action }}</div>
  @endisset
</div>
