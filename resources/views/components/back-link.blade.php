{{-- The way back out of a screen, drawn in the top bar. --}}
{{-- @var string $href --}}
@props([
  'href',
])

<x-button.bar :href="$href" data-turbo="true" size="small" {{ $attributes }}>
  <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />

  {{ $slot }}
</x-button.bar>
