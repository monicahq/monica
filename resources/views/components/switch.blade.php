{{-- A setting that is on or off, flicked rather than ticked. Goes inside the label that names it. --}}
{{--
  @var string $id
  @var string|null $name
  @var string $value
  @var bool $checked
  @var bool $disabled
--}}
@props([
  'id',
  'name' => null,
  'value' => '1',
  'checked' => false,
  'disabled' => false,
])

<input
  type="checkbox"
  role="switch"
  id="{{ $id }}"
  name="{{ $name ?? $id }}"
  value="{{ $value }}"
  @checked($checked)
  @disabled($disabled)
  {{ $attributes->class(['peer sr-only']) }}
/>

<span
  aria-hidden="true"
  class="relative block h-6 w-10 shrink-0 rounded-full border border-hairline-strong bg-sunken transition-colors duration-(--duration-fast) after:absolute after:top-0.5 after:left-0.5 after:size-4.5 after:rounded-full after:bg-canvas after:shadow-control after:transition-transform after:duration-(--duration-fast) after:content-[''] peer-checked:border-transparent peer-checked:bg-action-primary peer-checked:after:translate-x-4 peer-focus-visible:ring-[3px] peer-focus-visible:ring-focus-ring/45 peer-disabled:opacity-50"
></span>
