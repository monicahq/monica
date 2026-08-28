{{--
  The filled red button, which appears only at the last step of something that
  cannot be undone.

  It also opens the confirmation, from inside a panel whose whole subject is ending
  something: a red edge and a red band around a red button say what the region is
  before a word of it is read. Everywhere else a destructive action starts as a
  secondary or a ghost button, because a red button loose on an ordinary screen is
  a red button somebody presses by accident.

  Either way what it opens is the confirmation, and the confirmation ends with this
  same button, once the consequence has been named.

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
