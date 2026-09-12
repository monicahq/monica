{{-- The button that destroys something, with the card that asks first and the warning that cannot be left out. --}}
{{--
  @var string $title
  @var string $confirm
  @var string $action
  @var string $label
  @var string $variant
  @var string $size
--}}
@props([
  'title',
  'confirm',
  'action',
  'label',
  'variant' => 'secondary',
  'size' => 'default',
])

@php
  $tint = match ($variant) {
      'ghost' => 'hover:text-error',
      default => 'text-error',
  };
@endphp

<x-modal.warning :title="$title" :confirm="$confirm" :action="$action" {{ $attributes }}>
  <x-slot:trigger>
    <x-button
      :variant="$variant"
      :href="null"
      type="button"
      :size="$size"
      :class="$tint"
      @click="open = true"
    >{{ $label }}</x-button>
  </x-slot:trigger>

  {{ $slot }}

  <p>{{ __('This cannot be undone.') }}</p>
</x-modal.warning>
