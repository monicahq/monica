{{-- A bounded unit of content, with a header that names it and an optional mark beside the name. --}}
{{--
  @var string|null $title
  @var string|null $subtitle
  @var string|null $icon
  @var string|null $tint
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
  'tint' => null,
  'flush' => false,
  'variant' => 'default',
  'tone' => 'default',
])

@php
  $danger = $tone === 'danger';

  $surface = $variant === 'inset' ? 'bg-card' : 'bg-canvas';
  $edge = $danger ? 'border-error-edge' : 'border-hairline';
  $band = $danger ? 'border-error-edge bg-error-tint' : 'border-hairline';
  $mark = $danger ? 'border-transparent bg-error text-white' : ($tint ?? 'border-hairline bg-card text-body');

  $align = $subtitle ? 'items-start' : 'items-center';
@endphp

<section {{ $attributes->class(['overflow-hidden rounded-lg border shadow-panel', $edge, $surface]) }}>
  @if ($title)
    <header class="flex min-h-12 {{ $align }} gap-3 border-b px-4 py-3.5 {{ $band }}">
      @if ($icon)
        <span class="flex size-7 shrink-0 items-center justify-center rounded-md border {{ $mark }}">
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

  <div @class(['p-4' => ! $flush])>
    {{ $slot }}
  </div>

  @isset($footer)
    <footer {{ $footer->attributes->class(['border-t border-hairline bg-card px-4 py-3 text-sm']) }}>{{ $footer }}</footer>
  @endisset
</section>
