{{--
  One entry of the menu: a mark in the colour of the kind of thing it is, its name,
  and a line under it saying how much of it there is.

  A tile is one of three things, and it is the tile that decides which. Something
  that changes state is a form, so signing out cannot happen by following a link.
  Something that goes somewhere is a link. Something that has not been built yet is
  neither, and is drawn without anything suggesting it can be pressed.

  @var array{label: string, icon: string, iconClass: string, note: string|null, url: string|null, method: string|null} $tile
--}}
@props([
  'tile',
])

@php
  $method = $tile['method'] ?? null;
  $url = $tile['url'] ?? null;

  $base = 'flex items-center gap-2.5 rounded-md px-2.5 py-2.25 text-left text-base font-semibold no-underline '
    .'transition-[background-color] duration-(--duration-fast) ease-out '
    .'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring';

  $pressable = "{$base} cursor-pointer text-ink hover:bg-sand";
@endphp

@if ($method !== null)
  <x-form :method="$method" :action="$url">
    <button type="submit" class="{{ $pressable }} w-full">
      <x-launcher.tile-mark :tile="$tile" />
    </button>
  </x-form>
@elseif ($url !== null)
  <a href="{{ $url }}" data-turbo="true" class="{{ $pressable }}">
    <x-launcher.tile-mark :tile="$tile" />
  </a>
@else
  <span class="{{ $base }} text-placeholder">
    <x-launcher.tile-mark :tile="$tile" />
  </span>
@endif
