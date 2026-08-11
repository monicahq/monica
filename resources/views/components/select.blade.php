{{--
  A field that picks one of a fixed list, with its label, its hint and its
  validation messages. The options are the slot, so a caller writes its own
  <option> tags and keeps whatever it needs on them.

  It is a sibling of <x-input> rather than a branch inside it, because a select
  holds its value in its children rather than in an attribute.

  The arrow is a real icon laid over the control rather than a background image,
  so it takes the muted colour and follows it into dark.

  A class written on it lands on the field rather than on the control, since the
  control fills the field: <x-select class="w-38"> is a narrow field, not a narrow
  select inside a full-width one.

  @var string|null $id
  @var string|null $name
  @var string|null $label
  @var bool $optional
  `help` is a sentence under the control. It can be given as an attribute or as a
  named slot, which is what a hint that changes as the field changes needs.

  @var string|\Illuminate\View\ComponentSlot|null $help
  @var string|array<int, string>|null $error
  @var bool $required
  @var string $size
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
])

@php
  $helpId = $help ? $id.'-help' : null;
  $errorId = $error ? $id.'-error' : null;
  $describedBy = trim(($helpId ?? '').' '.($errorId ?? ''));

  /* The same well <x-input> draws, with room on the right for the arrow. */
  $control = 'w-full appearance-none rounded-md border border-hairline-strong bg-input pl-3 pr-8.5 font-sans text-ink '
    .'shadow-press-inset transition-[border-color,box-shadow] duration-(--duration-fast) ease-out '
    .'hover:border-muted '
    .'focus:border-ink focus:shadow-press-focus focus:outline-none '
    .'disabled:cursor-default disabled:bg-sand disabled:text-muted disabled:shadow-none '
    .'aria-[invalid=true]:border-error';
@endphp

<div class="flex flex-col gap-1.5 {{ $attributes->get('class') }}">
  @if ($label)
    <x-label :for="$id" :value="$label" :optional="$optional" />
  @endif

  <div class="relative">
    <select
      id="{{ $id }}"
      name="{{ $name ?? $id }}"
      @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
      @if ($error) aria-invalid="true" @endif
      {{ $required ? 'required' : '' }}
      {{ $attributes->except('class')->class([$control, $size === 'small' ? 'h-9 text-[16px] sm:text-sm' : 'h-11 text-[16px] sm:text-base']) }}
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
