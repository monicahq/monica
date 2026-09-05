{{-- The label of a form field. --}}
{{--
  @var string|null $value
  @var bool $optional
  @var bool $required
--}}
@props([
  'value' => null,
  'optional' => false,
  'required' => false,
])

<label {{ $attributes->class(['flex items-baseline gap-1.5 text-label text-ink']) }}>
  {{ $value ?? $slot }}

  @if ($optional)
    <span class="text-xs text-muted">{{ __('optional') }}</span>
  @endif

  @if ($required)
    <span class="text-xs text-muted">{{ __('required') }}</span>
  @endif
</label>
