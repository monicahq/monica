{{-- Giving the password up, and setting one again. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
@if ($viewModel->usesPasswordlessSignIn())
  <x-form method="post" :action="route('settings.password.create')">
    <x-panel>
      <div class="flex flex-col gap-4.5">
        <x-notice icon="lock" tone="info">{{ __('This account has no password. Your passkeys, and a link sent to your email address, are what sign you in.') }}</x-notice>

        <p class="text-sm text-pretty text-muted">{{ __('You can set one again at any time.') }}</p>

        <x-input
          type="password"
          id="new_password"
          name="new_password"
          :label="__('New password')"
          autocomplete="new-password"
          :error="$errors->first('new_password')"
          required
        />

        <x-input
          type="password"
          id="new_password_confirmation"
          name="new_password_confirmation"
          :label="__('Confirm new password')"
          autocomplete="new-password"
          required
        />

        <div class="flex justify-end">
          <x-button>{{ __('Set a password') }}</x-button>
        </div>
      </div>
    </x-panel>
  </x-form>
@elseif ($viewModel->canRemoveThePassword())
  <x-panel>
    <div x-data="{ confirming: false }" class="flex flex-col gap-4.5">
      <p class="text-sm text-pretty text-muted">{{ __('A password is the one credential somebody can phish from you. With a passkey on a device you hold, you do not need it.') }}</p>

      <div x-show="! confirming" class="flex justify-end">
        <x-button.secondary size="small" type="button" @click="confirming = true">{{ __('Remove my password') }}</x-button.secondary>
      </div>

      <div x-cloak x-show="confirming" class="flex flex-col gap-3">
        <x-notice icon="reminder" tone="warning">{{ __('Your password will no longer sign you in. Your passkeys, and a link sent to your email address, still will. You can set a password again later.') }}</x-notice>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep my password') }}</x-button.secondary>

          <x-form method="delete" :action="route('settings.password.destroy')">
            <x-button.danger size="small">{{ __('Remove my password') }}</x-button.danger>
          </x-form>
        </div>
      </div>
    </div>
  </x-panel>
@endif
