{{--
  The label of a form field.

  A field nobody has to fill in says so, rather than leaving somebody to work
  it out by submitting and seeing what comes back. Marking the optional ones is
  the way round that scales: most fields on a form are required, so marking
  those instead would put a badge on nearly every line.

  A form where that is the other way round, and nearly every field is optional,
  marks the one somebody has to fill instead. Which way round a form goes is the
  form's decision; asking for both at once would badge every line twice.

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
