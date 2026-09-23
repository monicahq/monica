{{-- A multi-line text field, with its label, its hint and its validation messages. --}}
{{--
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

  $control = 'w-full rounded-md border border-hairline-strong bg-input px-3 py-2.75 font-sans text-ink '
    .'transition duration-(--duration-fast) ease-out '
    .'placeholder:text-placeholder hover:border-muted-soft '
    .'focus-ring-field '
    .'disabled:cursor-default disabled:bg-hover disabled:text-muted '
    .'aria-[invalid=true]:border-error aria-[invalid=true]:ring-[3px] aria-[invalid=true]:ring-error/20 '
    .'min-h-24 resize-y text-[16px] leading-[1.6] sm:text-base';
@endphp

<div class="flex flex-col gap-1.5">
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
    {{ $attributes->class([$control]) }}
  >{{ $value }}</textarea>

  @if ($help)
    <p id="{{ $helpId }}" class="text-xs text-muted">{{ $help }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
