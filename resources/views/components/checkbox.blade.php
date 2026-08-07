{{--
  A checkbox and the sentence that goes with it. The real input is hidden behind
  the drawn box, which the peer variants fill in when it is checked, so there is
  no javascript involved and the keyboard still works.

  @var string $id
  @var string $value
  @var bool $checked
  @var bool $required
  @var string|array<int, string>|null $error
--}}
@props([
  'id',
  'value' => '1',
  'checked' => false,
  'required' => false,
  'error' => null,
])

<div class="space-y-1">
  <label for="{{ $id }}" class="flex cursor-pointer items-start gap-2.5">
    <input
      type="checkbox"
      id="{{ $id }}"
      name="{{ $id }}"
      value="{{ $value }}"
      @checked($checked)
      @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
      {{ $required ? 'required' : '' }}
      {{ $attributes->class(['peer sr-only']) }}
    />

    <span
      aria-hidden="true"
      class="mt-px flex size-4 shrink-0 items-center justify-center rounded-sm border-2 border-hairline-strong bg-canvas text-transparent transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-on-primary peer-focus-visible:ring-2 peer-focus-visible:ring-focus"
    >
      <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
        <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
      </svg>
    </span>

    <span class="text-sm leading-normal text-body">{{ $slot }}</span>
  </label>

  <x-error :id="$error ? $id.'-error' : null" :messages="$error" />
</div>
