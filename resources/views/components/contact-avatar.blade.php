{{-- How a contact is shown: their picture when there is one, their initials otherwise. --}}
{{--
  @var string $name
  @var string|null $url
  @var int $size
--}}
@props([
  'name',
  'url' => null,
  'size' => 34,
])

@if ($url)
  <x-image
    :src="$url"
    :alt="$name"
    :width="$size"
    :height="$size"
    {{ $attributes->class(['shrink-0 rounded-full object-cover']) }}
    style="width: {{ $size }}px; height: {{ $size }}px;"
  />
@else
  <x-avatar-initials :name="$name" :size="$size" {{ $attributes }} />
@endif
