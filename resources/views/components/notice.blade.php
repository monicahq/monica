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
   * The ground is the tone at a tenth, and the edge is the same tone rather than
   * the grey hairline the rest of the system uses, so a notice reads as one object
   * whichever tone it carries. The glyph beside the text is what actually says
   * which tone it is, and it says it without the colour.
   */
  $tints = [
    'info' => 'bg-brand/8 border-brand/25',
    'success' => 'bg-success/8 border-success/25',
    'warning' => 'bg-warning/8 border-warning/25',
    'danger' => 'bg-error/8 border-error/25',
    'quiet' => 'bg-sand border-hairline',
  ];

  $hues = [
    'info' => 'text-brand',
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
