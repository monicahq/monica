{{--
  A multi-line text field, with its label, its hint and its validation
  messages. It is <x-input> with room for more than one line, and it reads the
  same props so a form can swap one for the other.

  @var string|null $id
  @var string|null $name
  @var string|null $label
  @var bool $optional
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
  'optional' => false,
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
@endphp

<div class="erica-field">
  @if ($label)
    <x-label :for="$id" :value="$label" :optional="$optional" />
  @endif

  <textarea
    id="{{ $id }}"
    name="{{ $name ?? $id }}"
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    @if ($error) aria-invalid="true" @endif
    {{ $required ? 'required' : '' }}
    {{ $attributes->class(['erica-control erica-control--textarea']) }}
  >{{ $value }}</textarea>

  @if ($help)
    <p id="{{ $helpId }}" class="erica-field__help">{{ $help }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
