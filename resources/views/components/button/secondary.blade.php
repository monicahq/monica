{{--
  The neutral button, for the action that walks away from the primary one: cancel,
  back, or the way out of a screen somebody opened by mistake.

  It carries a hairline and a white surface, so it reads as a button without
  competing with the primary beside it. Its heights are the primary's, so the two
  line up side by side.

  @var string|null $href
  @var string $type
  @var string $size
--}}
@props([
  'href' => null,
  'type' => 'submit',
  'size' => 'default',
])

<x-button variant="secondary" :href="$href" :type="$type" :size="$size" {{ $attributes }}>
  @isset($icon)
    <x-slot:icon>{{ $icon }}</x-slot:icon>
  @endisset

  {{ $slot }}
</x-button>
