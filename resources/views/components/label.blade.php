{{--
  The label of a form field.

  A field nobody has to fill in says so, rather than leaving somebody to work
  it out by submitting and seeing what comes back. Marking the optional ones is
  the way round that scales: most fields on a form are required, so marking
  those instead would put a badge on nearly every line.

  @var string|null $value
  @var bool $optional
--}}
@props([
  'value' => null,
  'optional' => false,
])

<label {{ $attributes->class(['block text-xs leading-tight font-semibold text-ink']) }}>
  {{ $value ?? $slot }}

  @if ($optional)
    <span class="ml-1 font-normal text-muted-soft">{{ __('optional') }}</span>
  @endif
</label>
