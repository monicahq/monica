{{-- The button that destroys something, with the card that asks first and the warning that cannot be left out. --}}
{{--
  @var string $title
  @var string $confirm
  @var string $action
  @var string $label
  @var string $variant
  @var string $size
  @var bool $confirmsPassword
  @var bool $irreversible  false when what it deletes can still be restored, which leaves out the warning that it cannot be undone
  @var string|null $triggerLabel  the name of the button when its words alone do not say what it deletes
--}}
@props([
  'title',
  'confirm',
  'action',
  'label',
  'variant' => 'secondary',
  'size' => 'default',
  'confirmsPassword' => false,
  'irreversible' => true,
  'triggerLabel' => null,
])

<x-modal.warning :title="$title" :confirm="$confirm" :action="$action" :confirms-password="$confirmsPassword" {{ $attributes }}>
  <x-slot:trigger>
    <x-button
      :variant="$variant"
      :href="null"
      type="button"
      :size="$size"
      tone="danger"
      :aria-label="$triggerLabel"
      @click="open = true"
    >{{ $label }}</x-button>
  </x-slot:trigger>

  {{ $slot }}

  @if ($irreversible)
    <p>{{ __('This cannot be undone.') }}</p>
  @endif
</x-modal.warning>
