{{--
  How somebody is shown: the photo they uploaded when they have one, and their
  initials otherwise.

  The photo is served at the size it is displayed at and at twice it, so a dense
  screen picks the sharp one out of the srcset. Both of those sizes are written
  to disk on upload, so nothing here can ask for a size that does not exist.

  @var \App\Models\Employee|null $employee
  @var string $name
  @var int $size
--}}
@props([
  'employee' => null,
  'name',
  'size' => 34,
])

@if ($employee?->hasPhoto())
  @php
    $url = fn (int $pixels): string => route('settings.photo.show', ['employee' => $employee, 'size' => $pixels]);
  @endphp

  <x-image
    :src="$url(\App\Models\Employee::PHOTO_SIZE)"
    :srcset="$url(\App\Models\Employee::PHOTO_SIZE).' 1x, '.$url(\App\Models\Employee::PHOTO_SIZE * 2).' 2x'"
    :alt="$name"
    :width="$size"
    :height="$size"
    loading="eager"
    {{ $attributes->class(['shrink-0 rounded-full object-cover']) }}
    style="width: {{ $size }}px; height: {{ $size }}px;"
  />
@else
  <x-avatar-initials :name="$name" :size="$size" {{ $attributes }} />
@endif
