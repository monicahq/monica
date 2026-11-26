{{-- Sending a record of an entity type to the vault's trash. --}}
{{-- @var \App\ViewModels\Vaults\EntityFormViewModel $viewModel --}}
<x-delete-section :title="__('Delete :name', ['name' => $viewModel->name()])">
  <p>{{ __('It goes to the trash of this vault, where it waits 30 days before it is deleted for good.') }}</p>

  <x-slot:trigger>
    <x-delete-action
      :title="__('Delete “:name”?', ['name' => $viewModel->name()])"
      :confirm="__('Delete')"
      :action="$viewModel->destroyUrl()"
      :label="__('Delete :name', ['name' => $viewModel->name()])"
      :irreversible="false"
      variant="danger"
    >
      <p>{{ __('It goes to the trash of this vault. You can restore it from the vault settings for 30 days.') }}</p>
    </x-delete-action>
  </x-slot:trigger>
</x-delete-section>
