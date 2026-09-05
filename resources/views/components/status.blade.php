{{-- The short confirmation shown after something went right. --}}
{{-- @var string|null $message --}}
@props([
  'message' => null,
])

<div role="status" {{ $attributes->class(['rounded-lg border border-hairline bg-canvas px-4 py-3 text-sm text-ink', 'hidden' => ! $message]) }}>
  {{ $message }}
</div>
