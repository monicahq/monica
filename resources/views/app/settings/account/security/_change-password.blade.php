{{-- The password half of the security screen. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
@if ($viewModel->usesPasswordlessSignIn())
@else
  <x-form
    method="put"
    :action="route('settings.password.update')"
    id="password-form"
    x-target="password-form"
    class="dim-when-busy"
  >
    <x-panel>
      <div class="flex flex-col gap-4.5">
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
            :passwordrules="$viewModel->passwordRules()"
            :help="$viewModel->passwordRequirements()"
            :error="$errors->get('new_password')"
            allowPasswordManager
            required
          />

          <x-input
            type="password"
            id="new_password_confirmation"
            :label="__('Confirm new password')"
            autocomplete="new-password"
            :passwordrules="$viewModel->passwordRules()"
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

          <x-button size="small" class="ms-auto" type="submit" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Save') }}</x-button>
        </div>
      </x-slot:footer>
    </x-panel>
  </x-form>
@endif
