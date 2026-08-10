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

<label {{ $attributes->class(['block text-xs leading-tight font-semibold text-ink']) }}>
  {{ $value ?? $slot }}

  @if ($optional)
    <span class="ml-1 font-normal text-muted-soft">{{ __('optional') }}</span>
  @endif

  @if ($required)
    <span class="ml-1 font-normal text-body">{{ __('required') }}</span>
  @endif
</label>
