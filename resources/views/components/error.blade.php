{{--
  The validation messages of a field. Takes a single message or a list of them,
  and renders nothing at all when there is none.

  @var string|array<int, string>|null $messages
--}}
@props([
  'messages',
])

@if ($messages)
  <ul {{ $attributes->merge(['class' => 'space-y-1 text-xs text-error']) }}>
    @foreach ((array) $messages as $message)
      <li>{{ $message }}</li>
    @endforeach
  </ul>
@endif
