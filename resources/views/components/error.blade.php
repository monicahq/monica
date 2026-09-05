{{-- The validation messages of a field. --}}
{{-- @var string|array<int, string>|null $messages --}}
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
