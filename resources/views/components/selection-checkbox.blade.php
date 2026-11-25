{{-- A box with no sentence beside it, selecting the row or the list it sits in. --}}
{{-- @var string $label --}}
@props([
  'label',
])

<label class="relative z-10 flex size-6 cursor-pointer items-center justify-center">
  <input
    type="checkbox"
    aria-label="{{ $label }}"
    {{ $attributes->class(['size-4 cursor-pointer accent-ink focus-ring']) }}
  />
</label>
