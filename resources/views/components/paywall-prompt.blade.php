{{-- Told at the ceiling of the free plan: what the ceiling is, and where full access is bought. --}}
{{-- @var string $message --}}
@props([
  'message',
])

<x-notice tone="warning" :title="__('You are at the ceiling of the free plan')" {{ $attributes }}>
  {{ $message }}

  <x-slot:action>
    <x-button.secondary :href="route('settings.billing.index')" size="small" data-turbo="true">{{ __('See the plans') }}</x-button.secondary>
  </x-slot:action>
</x-notice>
