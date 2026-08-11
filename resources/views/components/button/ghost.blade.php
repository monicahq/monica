{{--
  The quietest button: no fill and no edge until the pointer is on it.

  It is for an action that belongs beside a heavier one without being offered as
  hard, such as removing the photo that has just been chosen. It is still a button
  and still reaches 34 or 40px, so it is not a link pretending to be one.

  @var string|null $href
  @var string $type
  @var string $size
--}}
@props([
  'href' => null,
  'type' => 'submit',
  'size' => 'default',
])

<x-button variant="ghost" :href="$href" :type="$type" :size="$size" {{ $attributes }}>
  @isset($icon)
    <x-slot:icon>{{ $icon }}</x-slot:icon>
  @endisset

  {{ $slot }}
</x-button>
