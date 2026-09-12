{{-- One labelled value of a detail list. --}}
{{--
  @var string $label
  @var string $labelStyle
  @var string $valueClass
--}}
@aware([
  'labelStyle' => 'plain',
])

@props([
  'label',
  'labelStyle' => 'plain',
  'valueClass' => 'text-base text-pretty text-ink',
])

@php
  $term = match ($labelStyle) {
      'caption' => 'text-[11px] font-medium tracking-caption text-muted-soft uppercase',
      default => 'text-sm text-muted',
  };
@endphp

<div
  {{
    $attributes->class([
      'grid grid-cols-[var(--detail-label-width)_minmax(0,1fr)] items-baseline gap-4',
      'border-b border-hairline px-4.5 py-3 last:border-b-0',
      'max-[560px]:grid-cols-1 max-[560px]:gap-0.5',
    ])
  }}
>
  <dt class="{{ $term }}">{{ $label }}</dt>

  <dd @class(['m-0 min-w-0', $valueClass])>{{ $slot }}</dd>
</div>
