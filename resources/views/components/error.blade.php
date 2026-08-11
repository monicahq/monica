{{--
  The validation messages of a field. Takes a single message or a list of them,
  and renders nothing at all when there is none.

  Each message carries a glyph as well as the colour, because a red sentence is
  not a signal to somebody who cannot see that it is red.

  @var string|array<int, string>|null $messages
--}}
@props([
  'messages',
])

@if ($messages)
  <ul {{ $attributes->class(['space-y-1']) }}>
    @foreach ((array) $messages as $message)
      <li class="flex items-start gap-1.5 text-xs text-error">
        <x-icon name="info" :size="14" :stroke-width="1.6" class="mt-px" />
        {{ $message }}
      </li>
    @endforeach
  </ul>
@endif
