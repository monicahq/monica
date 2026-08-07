{{--
  A text field, with its label, its hint and its validation messages. The id is
  also the name of the field, so one prop drives both.

  @var string $type
  @var string|null $id
  @var string|null $label
  @var string|null $value
  @var string|null $placeholder
  @var string|null $help
  @var string|array<int, string>|null $error
  @var string|null $autocomplete
  @var bool $required
  @var bool $autofocus
  @var bool $disabled
  @var bool $allowPasswordManager
--}}
@props([
  'type' => 'text',
  'id' => null,
  'label' => null,
  'value' => null,
  'placeholder' => null,
  'help' => null,
  'error' => null,
  'autocomplete' => null,
  'required' => false,
  'autofocus' => false,
  'disabled' => false,
  'allowPasswordManager' => false,
])

@php
  /* The hint and the errors are announced with the field rather than read as
     loose text somewhere after it, so they are pointed at by id. */
  $helpId = $help ? $id.'-help' : null;
  $errorId = $error ? $id.'-error' : null;
  $describedBy = trim(($helpId ?? '').' '.($errorId ?? ''));

  /* Safari on iOS zooms the page in whenever a field it is given is under 16px,
     so the field is 16px on a phone and back to 14px from sm up. */
  $classes = [
    'block w-full appearance-none',
    'px-3 py-2.5 text-base sm:text-sm',
    'rounded-md border border-hairline-strong bg-input text-ink placeholder-placeholder',
    'transition-colors duration-150',
    'hover:border-focus hover:bg-hover',
    'focus:border-focus focus:bg-canvas focus:outline-none',
    'disabled:cursor-default disabled:opacity-60',
    'aria-invalid:border-error',
  ];
@endphp

<div class="space-y-1.5">
  @if ($label)
    <x-label :for="$id" :value="$label" />
  @endif

  <input
    id="{{ $id }}"
    name="{{ $id }}"
    type="{{ $type }}"
    value="{{ $value }}"
    placeholder="{{ $placeholder }}"
    @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    @if ($error) aria-invalid="true" @endif
    @unless ($allowPasswordManager) data-1p-ignore @endunless
    {{ $autofocus ? 'autofocus' : '' }}
    {{ $required ? 'required' : '' }}
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->class($classes) }}
  />

  @if ($help)
    <p id="{{ $helpId }}" class="text-xs text-muted">{{ $help }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
