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
  <div class="flex flex-wrap items-center gap-3.5">
    <x-button.secondary
      type="button"
      aria-disabled="true"
      aria-describedby="entity-type-delete-reason"
    >
      {{ __('Delete this type') }}
    </x-button.secondary>

    <p id="entity-type-delete-reason" class="max-w-[48ch] text-xs text-pretty text-muted">
      {{ __('This type cannot be deleted while records are filed under it. Turn it off above to stop new ones being made.') }}
    </p>
  </div>
@endif
