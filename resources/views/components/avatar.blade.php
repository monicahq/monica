{{--
  How somebody is shown: the photo they uploaded when they have one, and their
  initials otherwise.

  The photo is served at the size it is displayed at and at twice it, so a dense
  screen picks the sharp one out of the srcset. Both of those sizes are written
  to disk on upload, so nothing here can ask for a size that does not exist.

  @var \App\Models\User $user
  @var int $size
--}}
@props([
  'user',
  'size' => 34,
])

@if ($user->hasPhoto())
  @php
    $url = fn (int $pixels): string => route('settings.photo.show', ['user' => $user, 'size' => $pixels]);
  @endphp

  <x-image
    :src="$url(\App\Models\User::PHOTO_SIZE)"
    :srcset="$url(\App\Models\User::PHOTO_SIZE).' 1x, '.$url(\App\Models\User::PHOTO_SIZE * 2).' 2x'"
    :alt="$user->name"
    :width="$size"
    :height="$size"
    loading="eager"
    {{ $attributes->class(['shrink-0 rounded-full object-cover']) }}
    style="width: {{ $size }}px; height: {{ $size }}px;"
  />
@else
  <x-avatar-initials :name="$user->name" :size="$size" {{ $attributes }} />
@endif
