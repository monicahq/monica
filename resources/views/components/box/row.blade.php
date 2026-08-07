{{--
  One row of a list drawn inside a <x-box>. Every list we show reads the same
  way, so the rule that separates two rows, the tint under the pointer and the
  corners live here rather than being written out again on each screen.

  Where a row sits in the panel is left to css. The first row takes the top
  rounding and the last one the bottom, so the tint never squares off a corner
  of the panel. The last row also drops its rule, since the panel's own border
  is right underneath it.

  That relies on the row actually being the last child of the panel, which is
  exactly what we want: when the panel ends with a `footer` slot, the row before
  it keeps its rule and the footer takes the bottom rounding instead.

  The box that holds rows is given padding="p-0", since each row brings its own.
  A row whose content already knows how to space itself, such as one built
  around a full width button, passes padding="p-0" in turn.

  Usage:
    <x-box padding="p-0">
      @foreach ($things as $thing)
        <x-box.row>{{ $thing->name }}</x-box.row>
      @endforeach

      <x-slot:footer><x-link :href="$next">{{ __('Load more') }}</x-link></x-slot:footer>
    </x-box>

  @var string $padding
--}}
@props([
  'padding' => 'px-3 py-3',
])

<div {{ $attributes->class(['border-b border-hairline-soft transition-colors duration-150 first:rounded-t-xl last:rounded-b-xl last:border-b-0 hover:bg-hover', $padding]) }}>
  {{ $slot }}
</div>
