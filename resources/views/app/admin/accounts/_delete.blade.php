{{-- Deleting an account and everything in it, from the admin space. --}}
{{-- @var \App\ViewModels\Admin\AccountViewModel $viewModel --}}
<x-delete-section :title="__('Delete this account')">
  <p>{{ __('The account goes with everything in it: :vaults, :contacts, every file it stored, every key and every session. Nothing can be recovered afterwards.', ['vaults' => $viewModel->vaultsLine(), 'contacts' => $viewModel->contactsLine()]) }}</p>

  @if ($viewModel->isBillable())
    <p>{{ __('A running subscription stops first. The days already paid for are not refunded.') }}</p>
  @endif

  @if ($viewModel->isTheViewer())
    <p id="account-deletion-refusal">{{ __('You cannot delete your own account from here. Delete it from your account settings instead.') }}</p>
  @endif

  <x-slot:trigger>
    @if ($viewModel->isTheViewer())
      <x-button.danger type="button" aria-disabled="true" aria-describedby="account-deletion-refusal">{{ __('Delete account') }}</x-button.danger>
    @else
      <x-modal.form
        :title="__('Delete this account for good?')"
        :description="__('Everything in it is deleted at once. The administrator log keeps the account number and nothing else.')"
        :confirm="__('Delete the account')"
        confirm-variant="danger"
        method="delete"
        :action="$viewModel->destroyUrl()"
        :open="$errors->has('confirmation')"
      >
        <x-slot:trigger>
          <x-button.danger type="button" @click="open = true">{{ __('Delete account') }}</x-button.danger>
        </x-slot:trigger>

        <x-input
          id="confirmation"
          name="confirmation"
          :label="$viewModel->deletionConfirmationLabel()"
          :help="__('Type :value to confirm.', ['value' => $viewModel->deletionConfirmation()])"
          :error="$errors->first('confirmation')"
          autocomplete="off"
          required
        />
      </x-modal.form>
    @endif
  </x-slot:trigger>
</x-delete-section>
