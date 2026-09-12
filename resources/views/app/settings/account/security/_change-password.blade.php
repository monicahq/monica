{{-- The password half of the security screen. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
@if ($viewModel->usesPasswordlessSignIn())
@elseif ($viewModel->usesSingleSignOn())
  <x-notice icon="lock">{{ __('You sign in through your identity provider, so there is no password to change here.') }}</x-notice>
@else
  <x-form
    method="put"
    :action="route('settings.password.update')"
    id="password-form"
    x-target="password-form"
    class="transition-opacity [&[aria-busy]]:opacity-60"
  >
    <x-panel>
      <div class="flex flex-col gap-4.5">
        <p class="text-sm text-pretty text-muted">{{ __('You stay signed in here, and your new password is what you type the next time.') }}</p>

        <div class="grid gap-4 sm:grid-cols-3">
          <x-input
            type="password"
            id="current_password"
            :label="__('Current password')"
            autocomplete="current-password"
            :error="$errors->get('current_password')"
            allowPasswordManager
            required
          />

          <x-input
            type="password"
            id="new_password"
            :label="__('New password')"
            autocomplete="new-password"
            passwordrules="minlength: 8"
            :help="__('Minimum 8 characters.')"
            :error="$errors->get('new_password')"
            allowPasswordManager
            required
          />

          <x-input
            type="password"
            id="new_password_confirmation"
            :label="__('Confirm new password')"
            autocomplete="new-password"
            passwordrules="minlength: 8"
            :error="$errors->get('new_password_confirmation')"
            allowPasswordManager
            required
          />
        </div>
      </div>

      <x-slot:footer>
        <div class="flex items-center gap-4">
          <span class="text-xs text-pretty text-muted">
            @if ($viewModel->passwordChangedAt())
              {{ __('Last changed :time', ['time' => $viewModel->passwordChangedAt()]) }}
            @endif
          </span>

          <x-button size="small" class="ml-auto" type="submit" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Save') }}</x-button>
        </div>
      </x-slot:footer>
    </x-panel>
  </x-form>
@endif
