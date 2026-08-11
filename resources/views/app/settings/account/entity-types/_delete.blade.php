{{--
  Deleting a kind of record. It is only offered while the type holds nothing:
  once records exist, they go first and deliberately, because taking a hundred
  animals down with the word "Animal" is not something one button should do.

  A type that holds something says so in place of the button, so the screen
  explains the refusal rather than hiding the option and leaving somebody
  looking for it.

  @var \App\ViewModels\Settings\Account\EntityTypes\EntityTypeFormViewModel $viewModel
--}}
@if ($viewModel->canBeDeleted())
  <x-modal.warning
    :title="__('Delete “:name”?', ['name' => $viewModel->entityType()->name_plural])"
    :confirm="__('Delete the type')"
    :action="route('settings.entityTypes.destroy', $viewModel->entityType())"
  >
    <x-slot:trigger>
      <x-button.secondary type="button" @click="open = true" class="text-error">
        {{ __('Delete this type') }}
      </x-button.secondary>
    </x-slot:trigger>

    <p>{{ __('Nothing is filed under it, so it goes for good and stops appearing in every vault.') }}</p>

    <p>{{ __('This cannot be undone.') }}</p>
  </x-modal.warning>
@else
  <p class="erica-caption max-w-[48ch]">
    {{ __('This type cannot be deleted while records are filed under it. Turn it off above to stop new ones being made.') }}
  </p>
@endif
