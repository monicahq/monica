{{-- An inline message attached to the thing it is about, rather than a banner at the top. --}}
{{--
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

    <p class="mt-0.75 text-sm text-pretty text-body">{{ $slot }}</p>
  </div>

  @isset($action)
    <div class="ml-auto shrink-0">{{ $action }}</div>
  @endisset
</div>
