{{-- Deleting a kind of record. --}}
{{-- @var \App\ViewModels\Settings\Account\EntityTypes\EntityTypeFormViewModel $viewModel --}}
<x-delete-section :title="__('Delete this type')">
  @unless ($viewModel->canBeDeleted())
    <p id="entity-type-delete-reason">{{ __('This type cannot be deleted while records are filed under it. Turn it off above to stop new ones being made.') }}</p>
  @endunless

  <x-slot:trigger>
    @if ($viewModel->canBeDeleted())
      <x-delete-action
        :title="__('Delete “:name”?', ['name' => $viewModel->entityType()->name_plural])"
        :confirm="__('Delete the type')"
        :action="route('settings.entityTypes.destroy', $viewModel->entityType())"
        :label="__('Delete this type')"
        variant="danger"
      >
        <p>{{ __('Nothing is filed under it, so it is deleted for good and stops appearing in every vault.') }}</p>
      </x-delete-action>
    @else
      <x-button.danger
        type="button"
        aria-disabled="true"
        aria-describedby="entity-type-delete-reason"
      >
        {{ __('Delete this type') }}
      </x-button.danger>
    @endif
  </x-slot:trigger>
</x-delete-section>
