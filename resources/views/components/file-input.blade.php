{{--
  A file field, with its label, its hint and its validation messages. It is a
  sibling of x-input rather than a branch inside it, because a file field has no
  value to carry and takes an accept list instead.

  The id is also the name of the input, so one prop drives both. A screen that
  names it after something it holds rather than after itself, as a repeating
  block does, gives `name` separately, exactly as <x-input> does.

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
  /*
   * A file field is the browser's own control, because nothing hand-built opens a
   * file picker as reliably. What is drawn is the native button dressed as a small
   * secondary one, with the chosen file's name beside it as a caption. So the well
   * is dropped here: there is no text being typed into it.
   */
  $control = 'cursor-pointer text-xs text-muted '
    .'file:mr-3 file:h-8.5 file:cursor-pointer file:rounded-md file:border file:border-hairline-strong '
    .'file:bg-canvas file:px-3.25 file:font-sans file:text-[13.5px] file:font-semibold file:text-ink '
    .'file:shadow-raised file:transition-[background-color] file:duration-(--duration-fast) file:ease-out '
    .'hover:file:bg-sand';
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
