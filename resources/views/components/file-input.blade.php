{{--
  A file field, with its label, its hint and its validation messages. It is a
  sibling of x-input rather than a branch inside it, because a file field has no
  value to carry and takes an accept list instead.

  @var string|null $id
  @var string|null $label
  @var string|null $accept
  @var string|null $help
  @var string|array<int, string>|null $error
  @var bool $required
--}}
@props([
  'id' => null,
  'label' => null,
  'accept' => null,
  'help' => null,
  'error' => null,
  'required' => false,
])

@php
  $helpId = $help ? $id.'-help' : null;
  $errorId = $error ? $id.'-error' : null;
  $describedBy = trim(($helpId ?? '').' '.($errorId ?? ''));
@endphp

<div class="erica-field">
  @if ($label)
    <x-label :for="$id" :value="$label" />
  @endif

  <input
    id="{{ $id }}"
    name="{{ $id }}"
    type="file"
    @if ($accept) accept="{{ $accept }}" @endif
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    @if ($error) aria-invalid="true" @endif
    {{ $required ? 'required' : '' }}
    {{ $attributes->class(['erica-control erica-control--file']) }}
  />

  @if ($help)
    <p id="{{ $helpId }}" class="erica-field__help">{{ $help }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
