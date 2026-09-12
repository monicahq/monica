{{-- Removing a kind of link, which only ever applies to one nobody has used. --}}
{{-- @var \App\ViewModels\Settings\Account\RelationshipTypes\RelationshipTypeFormViewModel $viewModel --}}
@if ($viewModel->canBeDeleted())
  <x-delete-action
    :title="__('Delete “:name”?', ['name' => $viewModel->relationshipType()->name])"
    :confirm="__('Delete the type')"
    :action="route('settings.relationshipTypes.destroy', $viewModel->relationshipType())"
    :label="__('Delete this type')"
  >
    <p>{{ __('No relationship is written with this type, so nothing you have written will change.') }}</p>
  </x-delete-action>
@else
  <p class="max-w-[48ch] text-xs text-pretty text-muted">{{ $viewModel->deleteBlockedReason() }}</p>
@endif
