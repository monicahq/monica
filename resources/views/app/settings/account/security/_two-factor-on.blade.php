{{-- The authenticator app, once it answers the second step of signing in. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<x-row
  :title="__('Authenticator app')"
  :description="__('Turned on :time. We ask for a code every time you sign in.', ['time' => $viewModel->twoFactorConfirmedAt()])"
  icon="lock"
>
  <x-slot:end>
    <x-badge tone="success" icon="check">{{ __('On') }}</x-badge>

    <div x-data="{ confirming: false }">
      <x-button.secondary size="small" type="button" x-show="! confirming" @click="confirming = true">{{ __('Turn it off') }}</x-button.secondary>

      <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
        <p class="text-xs text-error">{{ $viewModel->authenticatorAppRemovalWarning() }}</p>

        <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep it on') }}</x-button.secondary>

        <x-form method="delete" :action="route('settings.twoFactor.destroy')">
          <x-button.danger size="small" type="submit" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Turn it off') }}</x-button.danger>
        </x-form>
      </div>
    </div>
  </x-slot:end>
</x-row>
