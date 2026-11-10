{{-- A field that picks one of a fixed list, with its label, its hint and its validation messages. --}}
{{--
  @var string|null $id
  @var string|null $name
  @var string|null $label
  @var bool $optional
  @var string|\Illuminate\View\ComponentSlot|null $help
  @var string|array<int, string>|null $error
  @var bool $required
  @var string $size
  @var bool $inline
--}}
@props([
  'id' => null,
  'name' => null,
  'label' => null,
  'optional' => false,
  'help' => null,
  'error' => null,
  'required' => false,
  'size' => 'default',
  'inline' => false,
])

@php
  $helpId = $help ? $id.'-help' : null;
  $errorId = $error ? $id.'-error' : null;
  $describedBy = trim(($helpId ?? '').' '.($errorId ?? ''));

  $control = 'w-full appearance-none rounded-md border border-hairline-strong bg-input pl-3 pr-8.5 font-sans text-ink '
    .'transition duration-(--duration-fast) ease-out '
    .'hover:border-muted-soft '
    .'focus-ring-field '
    .'disabled:cursor-default disabled:bg-hover disabled:text-muted '
    .'aria-[invalid=true]:border-error aria-[invalid=true]:ring-[3px] aria-[invalid=true]:ring-error/20';
@endphp

<div class="flex {{ $inline ? 'items-center gap-2' : 'flex-col gap-1.5' }} {{ $attributes->get('class') }}">
  @if ($label)
    <x-label :for="$id" :value="$label" :optional="$optional" :class="$inline ? 'whitespace-nowrap' : null" />
  @endif

  <div @class(['relative', 'min-w-0 flex-1' => $inline])>
    <select
      id="{{ $id }}"
      name="{{ $name ?? $id }}"
      @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
      @if ($error) aria-invalid="true" @endif
      {{ $required ? 'required' : '' }}
      {{ $attributes->except('class')->class([$control, $size === 'small' ? 'h-8 text-[16px] sm:text-sm' : 'h-9 text-[16px] sm:text-base']) }}
    >{{ $slot }}</select>

    <x-icon
      name="chevron-down"
      :size="16"
      :stroke-width="1.5"
      class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-muted"
    />
  </div>

  @if ($help)
    <p id="{{ $helpId }}" class="text-xs text-muted">{{ $help }}</p>
  @endif

  <x-error :id="$errorId" :messages="$error" />
</div>
