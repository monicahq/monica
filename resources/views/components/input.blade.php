{{-- A text field, with its label, its hint and its validation messages. --}}
{{--
  @var string $type
  @var string|null $id
  @var string|null $name
  @var string|null $label
  @var bool $optional
  @var bool $requiredMark
  @var string|null $value
  @var string|null $placeholder
  @var string|null $help
  @var string|array<int, string>|null $error
  @var string|null $autocomplete
  @var bool $required
  @var bool $autofocus
  @var bool $disabled
  @var bool $allowPasswordManager
  @var string $size
--}}
@props([
  'type' => 'text',
  'id' => null,
  'name' => null,
  'label' => null,
  'optional' => false,
  'requiredMark' => false,
  'value' => null,
  'placeholder' => null,
  'help' => null,
  'error' => null,
  'autocomplete' => null,
  'required' => false,
  'autofocus' => false,
  'disabled' => false,
  'allowPasswordManager' => false,
  'size' => 'default',
])

@php
  $helpId = $help ? $id.'-help' : null;
  $errorId = $error ? $id.'-error' : null;
  $describedBy = trim(($helpId ?? '').' '.($errorId ?? ''));

  $control = 'w-full rounded-md border border-hairline-strong bg-input px-3 font-sans text-ink '
    .'transition duration-(--duration-fast) ease-out '
    .'placeholder:text-placeholder hover:border-muted-soft '
    .'focus-ring-field '
    .'disabled:cursor-default disabled:bg-hover disabled:text-muted '
    .'aria-[invalid=true]:border-error aria-[invalid=true]:ring-[3px] aria-[invalid=true]:ring-error/20';
@endphp

<div class="flex flex-col gap-1.5">
  @if ($label)
    <x-label :for="$id" :value="$label" :optional="$optional" :required="$requiredMark" />
  @endif

  <input
    @if ($id) id="{{ $id }}" @endif
    name="{{ $name ?? $id }}"
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
    {{ $attributes->class([$control, $size === 'small' ? 'h-8 text-[16px] sm:text-sm' : 'h-9 text-[16px] sm:text-base']) }}
  />

  @if ($help)
    <p id="{{ $helpId }}" class="text-xs text-muted">{{ $help }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
