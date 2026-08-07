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

  /* 16px on a phone, the way x-input is, so tapping it does not zoom the page in. */
  $classes = [
    'block w-full cursor-pointer text-base text-muted sm:text-sm',
    'file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-hairline-strong',
    'file:bg-canvas file:px-3 file:py-2 file:text-base file:font-semibold file:text-ink sm:file:text-sm',
    'hover:file:bg-hover',
  ];
@endphp

<div class="space-y-1.5">
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
    {{ $attributes->class($classes) }}
  />

  @if ($help)
    <p id="{{ $helpId }}" class="text-xs text-muted">{{ $help }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
