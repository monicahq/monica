{{-- A short word about the state or the kind of something. --}}
{{--
  @var string $tone
  @var string|null $icon
--}}
@props([
  'tone' => 'neutral',
  'icon' => null,
])

@php
  $tint = match ($tone) {
    'success' => 'border-success-edge bg-success-tint text-success',
    'warning' => 'border-warning-edge bg-warning-tint text-warning',
    'danger' => 'border-error-edge bg-error-tint text-error',
    'info' => 'border-hairline bg-canvas text-body',
    'accent' => 'border-transparent bg-primary text-on-primary',
    default => 'border-transparent bg-hover text-body',
  };
@endphp

<span
  {{
    $attributes->class([
      'inline-flex h-5.5 items-center gap-1.25 rounded-full border px-2.25 font-sans text-2xs font-medium whitespace-nowrap',
      $tint,
    ])
  }}
>
  @if ($icon)
    <x-icon :name="$icon" :size="13" :stroke-width="1.6" />
  @endif

  {{ $slot }}
</span>
