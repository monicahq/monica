{{--
  The button that lives in the top bar, which is the way back out of the screen you
  are on.

  It is the secondary button on sand rather than on white, and that is the whole
  difference between them. The secondary is white because it sits on paper; the bar is
  not paper, and a white button on a white bar is a label with a border round it. In
  dark the two surfaces swap the other way and sand still sits above canvas, so the
  button stays lighter than its bar in both themes.

  Beside the search field it is the same material with the opposite elevation: this
  one has the hard bottom edge of an object, the field has the inset shade of a well.

  @var string|null $href
  @var string $type
  @var string $size
--}}
@props([
  'href' => null,
  'type' => 'submit',
  'size' => 'default',
])

<x-button variant="bar" :href="$href" :type="$type" :size="$size" {{ $attributes }}>
  @isset($icon)
    <x-slot:icon>{{ $icon }}</x-slot:icon>
  @endisset

  {{ $slot }}
</x-button>
