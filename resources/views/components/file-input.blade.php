{{-- A file field, with its label, its hint and its validation messages. --}}
{{--
  @var string|null $id
  @var string|null $name
  @var string|null $label
  @var string|null $accept
  @var string|null $help
  @var string|array<int, string>|null $error
  @var bool $required
--}}
@props([
  'id' => null,
  'name' => null,
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

@php
  $control = 'cursor-pointer text-xs text-muted '
    .'file:me-3 file:h-8 file:cursor-pointer file:rounded-md file:border file:border-hairline '
    .'file:bg-canvas file:px-3 file:font-sans file:text-sm file:font-medium file:text-ink '
    .'file:transition-colors file:duration-(--duration-fast) file:ease-out '
    .'hover:file:bg-hover';
@endphp

<div class="flex flex-col gap-1.5">
  @if ($label)
    <x-label :for="$id" :value="$label" />
  @endif

  <input
    id="{{ $id }}"
    name="{{ $name ?? $id }}"
    type="file"
    @if ($accept) accept="{{ $accept }}" @endif
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
    @if ($error) aria-invalid="true" @endif
    {{ $required ? 'required' : '' }}
    {{ $attributes->class([$control]) }}
  />

  @if ($help)
    <p id="{{ $helpId }}" class="text-xs text-muted">{{ $help }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
