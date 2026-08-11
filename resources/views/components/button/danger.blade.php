{{--
  The filled red button, which appears only at the last step of something that
  cannot be undone.

  A destructive action never starts here. It starts as a secondary or a ghost
  button that opens the confirmation, and this is what the confirmation ends with,
  once the consequence has been named.

  @var string|null $href
  @var string $type
  @var string $size
--}}
@props([
  'href' => null,
  'type' => 'submit',
  'size' => 'default',
])

<x-button variant="danger" :href="$href" :type="$type" :size="$size" {{ $attributes }}>
  @isset($icon)
    <x-slot:icon>{{ $icon }}</x-slot:icon>
  @endisset

  {{ $slot }}
</x-button>
