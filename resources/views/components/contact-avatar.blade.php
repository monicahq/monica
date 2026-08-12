{{--
  How a contact is shown: the picture somebody chose for them when there is one,
  and their initials otherwise.

  It takes the address of the picture rather than the contact, because the lists
  are built from arrays a view model assembled and a component that queried the
  database would query it once a row.

  The picture is one square file read through the application, so nothing here
  asks for a size: the browser draws it at the size the page gives it.

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
