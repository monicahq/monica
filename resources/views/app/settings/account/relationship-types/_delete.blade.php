{{-- Removing a kind of link, which only ever applies to one nobody has used. --}}
{{-- @var \App\ViewModels\Settings\Account\RelationshipTypes\RelationshipTypeFormViewModel $viewModel --}}
<x-delete-section :title="__('Delete this type')">
  @unless ($viewModel->canBeDeleted())
    <p id="relationship-type-delete-reason">{{ $viewModel->deleteBlockedReason() }}</p>
  @endunless

  <x-slot:trigger>
    @if ($viewModel->canBeDeleted())
      <x-delete-action
        :title="__('Delete “:name”?', ['name' => $viewModel->relationshipType()->name])"
        :confirm="__('Delete the type')"
        :action="route('settings.relationshipTypes.destroy', $viewModel->relationshipType())"
        :label="__('Delete this type')"
        variant="danger"
      >
        <p>{{ __('No relationship is written with this type, so nothing you have written will change.') }}</p>
      </x-delete-action>
    @else
      <x-button.danger
        type="button"
        aria-disabled="true"
        aria-describedby="relationship-type-delete-reason"
      >
        {{ __('Delete this type') }}
      </x-button.danger>
    @endif
  </x-slot:trigger>
</x-delete-section>
