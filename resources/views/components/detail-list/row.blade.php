{{-- One labelled value of a detail list. --}}
{{--
  @var string $label
  @var string $labelStyle
  @var string $layout
  @var string $valueClass
--}}
@aware([
  'labelStyle' => 'plain',
  'layout' => 'columns',
])

@props([
  'label',
  'labelStyle' => 'plain',
  'layout' => 'columns',
  'valueClass' => 'text-base text-pretty text-ink',
])

@php
  $term = match ($labelStyle) {
      'caption' => 'text-2xs font-medium tracking-caption text-muted uppercase',
      default => 'text-sm text-muted',
  };

  $arrangement = match ($layout) {
      'stacked' => 'grid-cols-1 gap-0.5',
      default => 'grid-cols-[var(--detail-label-width)_minmax(0,1fr)] items-baseline gap-4 max-xs:grid-cols-1 max-xs:gap-0.5',
  };
@endphp

<div
  {{
    $attributes->class([
      'grid border-b border-hairline px-4.5 py-3 last:border-b-0',
      $arrangement,
    ])
  }}
>
  <dt class="{{ $term }}">{{ $label }}</dt>

  <dd @class(['m-0 min-w-0', $valueClass])>{{ $slot }}</dd>
</div>
