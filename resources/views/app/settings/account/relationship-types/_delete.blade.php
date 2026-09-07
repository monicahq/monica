{{-- Removing a kind of link, which only ever applies to one nobody has used. --}}
{{-- @var \App\ViewModels\Settings\Account\RelationshipTypes\RelationshipTypeFormViewModel $viewModel --}}
@if ($viewModel->canBeDeleted())
  <x-modal.warning
    :title="__('Delete “:name”?', ['name' => $viewModel->relationshipType()->name])"
    :confirm="__('Delete the type')"
    :action="route('settings.relationshipTypes.destroy', $viewModel->relationshipType())"
  >
    <x-slot:trigger>
      <x-button.secondary type="button" @click="open = true" class="text-error">
        {{ __('Delete this type') }}
      </x-button.secondary>
    </x-slot:trigger>

    <p>{{ __('No relationship is written with this type, so nothing you have written will change.') }}</p>

    <p>{{ __('This cannot be undone.') }}</p>
  </x-modal.warning>
@else
  <p class="max-w-[48ch] text-xs text-pretty text-muted">{{ $viewModel->deleteBlockedReason() }}</p>
@endif
