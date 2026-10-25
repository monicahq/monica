{{-- The button that destroys something, with the card that asks first and the warning that cannot be left out. --}}
{{--
  @var string $title
  @var string $confirm
  @var string $action
  @var string $label
  @var string $variant
  @var string $size
  @var bool $confirmsPassword
--}}
@props([
  'title',
  'confirm',
  'action',
  'label',
  'variant' => 'secondary',
  'size' => 'default',
  'confirmsPassword' => false,
])

<x-modal.warning :title="$title" :confirm="$confirm" :action="$action" :confirms-password="$confirmsPassword" {{ $attributes }}>
  <x-slot:trigger>
    <x-button
      :variant="$variant"
      :href="null"
      type="button"
      :size="$size"
      tone="danger"
      @click="open = true"
    >{{ $label }}</x-button>
  </x-slot:trigger>

  {{ $slot }}

  <p>{{ __('This cannot be undone.') }}</p>
</x-modal.warning>
