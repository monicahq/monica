{{--
  A multi-line text field, with its label, its hint and its validation
  messages. It is <x-input> with room for more than one line, and it reads the
  same props so a form can swap one for the other.

  @var string|null $id
  @var string|null $name
  @var string|null $label
  @var string|null $value
  @var string|null $placeholder
  @var string|null $help
  @var string|array<int, string>|null $error
  @var int $rows
  @var bool $required
--}}
@props([
  'id' => null,
  'name' => null,
  'label' => null,
  'value' => null,
  'placeholder' => null,
  'help' => null,
  'error' => null,
  'rows' => 3,
  'required' => false,
])

@php
  $helpId = $help ? $id.'-help' : null;
  $errorId = $error ? $id.'-error' : null;
  $describedBy = trim(($helpId ?? '').' '.($errorId ?? ''));

  $classes = [
    'block w-full appearance-none resize-y',
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

  <textarea
    id="{{ $id }}"
    name="{{ $name ?? $id }}"
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    @if ($error) aria-invalid="true" @endif
    {{ $required ? 'required' : '' }}
    {{ $attributes->class($classes) }}
  >{{ $value }}</textarea>

  @if ($help)
    <p id="{{ $helpId }}" class="text-xs text-muted">{{ $help }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
