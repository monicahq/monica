{{--
  A text field, with its label, its hint and its validation messages. The id is
  also the name of the field, so one prop drives both.

  A screen that repeats the same field on every row of a list gives `name`
  separately, since the name is the same on each of them and the id cannot be.

  `required` makes the browser refuse an empty field; `requiredMark` writes the
  word beside the label. They are separate because most forms here require most
  of their fields, and a badge on nearly every line says nothing. A form where
  almost everything is optional asks for the mark on the one field that is not.

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
  A field in a bar rather than in a form is drawn small, which is the only size
  besides the default one and lines up with a small button beside it.

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
  /* The hint and the errors are announced with the field rather than read as
     loose text somewhere after it, so they are pointed at by id. */
  $helpId = $help ? $id.'-help' : null;
  $errorId = $error ? $id.'-error' : null;
  $describedBy = trim(($helpId ?? '').' '.($errorId ?? ''));

  /*
   * A control is a white box inside a hairline. Focus darkens that hairline to
   * near black and lays a soft ring of the same colour outside it, so which field
   * is being typed in is plain at a glance down a long form, and the ring is the
   * same shape a button draws when the keyboard reaches it.
   *
   * Safari on iOS zooms the page in whenever a field it is given is under 16px, so
   * every control is 16px on a phone and back down to its own size from sm up.
   *
   * <x-textarea> and <x-select> carry the same string. Three tags cannot share one
   * definition without a wrapper that hides which tag is being drawn.
   */
  $control = 'w-full rounded-md border border-hairline-strong bg-input px-3 font-sans text-ink '
    .'transition-[border-color,box-shadow] duration-(--duration-fast) ease-out '
    .'placeholder:text-placeholder hover:border-muted-soft '
    .'focus:border-focus focus:ring-[3px] focus:ring-focus-ring/20 focus:outline-none '
    .'disabled:cursor-default disabled:bg-hover disabled:text-muted '
    .'aria-[invalid=true]:border-error aria-[invalid=true]:ring-[3px] aria-[invalid=true]:ring-error/20';
@endphp

<div class="flex flex-col gap-1.5">
  @if ($label)
    <x-label :for="$id" :value="$label" :optional="$optional" :required="$requiredMark" />
  @endif

  <input
    id="{{ $id }}"
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
