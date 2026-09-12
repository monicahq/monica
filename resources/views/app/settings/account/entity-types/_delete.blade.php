{{-- Deleting a kind of record. --}}
{{-- @var \App\ViewModels\Settings\Account\EntityTypes\EntityTypeFormViewModel $viewModel --}}
@if ($viewModel->canBeDeleted())
  <x-delete-action
    :title="__('Delete “:name”?', ['name' => $viewModel->entityType()->name_plural])"
    :confirm="__('Delete the type')"
    :action="route('settings.entityTypes.destroy', $viewModel->entityType())"
    :label="__('Delete this type')"
  >
    <p>{{ __('Nothing is filed under it, so it is deleted for good and stops appearing in every vault.') }}</p>
  </x-delete-action>
@else
  <p class="max-w-[48ch] text-xs text-pretty text-muted">
    {{ __('This type cannot be deleted while records are filed under it. Turn it off above to stop new ones being made.') }}
  </p>
@endif
