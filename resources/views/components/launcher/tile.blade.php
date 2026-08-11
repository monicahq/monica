{{--
  One entry of the menu: a mark in the colour of the kind of thing it is, its name,
  and a line under it saying how much of it there is.

  A tile is one of three things, and it is the tile that decides which. Something
  that changes state is a form, so signing out cannot happen by following a link.
  Something that goes somewhere is a link. Something that has not been built yet is
  neither, and is drawn without anything suggesting it can be pressed.

  @var array{label: string, icon: string, hue: string, tint: string, note: string|null, url: string|null, method: string|null} $tile
--}}
@props([
  'tile',
])

@php
  $method = $tile['method'] ?? null;
  $url = $tile['url'] ?? null;
@endphp

@if ($method !== null)
  <x-form :method="$method" :action="$url">
    <button type="submit" class="erica-launcher__tile w-full">
      <x-launcher.tile-mark :tile="$tile" />
    </button>
  </x-form>
@elseif ($url !== null)
  <a href="{{ $url }}" data-turbo="true" class="erica-launcher__tile">
    <x-launcher.tile-mark :tile="$tile" />
  </a>
@else
  <span class="erica-launcher__tile erica-launcher__tile--unbuilt">
    <x-launcher.tile-mark :tile="$tile" />
  </span>
@endif
