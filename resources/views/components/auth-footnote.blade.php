{{-- The line under a sign-in card that offers the other way in. --}}
{{--
  @var string|null $href
  @var string|null $label
--}}
@props([
  'href' => null,
  'label' => null,
])

<x-box padding="p-4" {{ $attributes->class(['rounded-lg text-center text-sm text-body']) }}>
  {{ $slot }}

  @if ($href && $label)
    <x-link turbo :href="$href" class="font-semibold text-ink">{{ $label }}</x-link>
  @endif
</x-box>
