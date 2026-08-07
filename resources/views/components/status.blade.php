{{--
  The short confirmation shown after something went right. It is announced on
  its own, rather than read as loose text, because it appears after the page
  has already been read. It stays in the page while there is nothing to say, so
  a screen reader is already watching it when a message arrives.

  @var string|null $message
--}}
@props([
  'message' => null,
])

<div role="status" {{ $attributes->class(['rounded-lg border border-hairline bg-canvas px-4 py-3 text-sm text-ink', 'hidden' => ! $message]) }}>
  {{ $message }}
</div>
