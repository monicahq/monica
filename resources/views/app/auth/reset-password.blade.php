{{-- Where somebody who followed a reset link chooses a new password. --}}
{{-- @var \App\ViewModels\Auth\NewPasswordViewModel $viewModel --}}
<x-auth-page
  :title="__('Choose a new password')"
  :quote="$viewModel->quote()"
>
  <x-box>
    <x-form method="post" :action="route('auth.password.update')" class="space-y-4">
      <input type="hidden" name="token" value="{{ $viewModel->token() }}" />

      <x-input
        type="hidden"
        id="email"
        :value="$viewModel->email()"
        :error="$errors->get('email')"
        autocomplete="username"
        allowPasswordManager
      />

      <div class="space-y-1.5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <x-input
            type="password"
            id="password"
            :label="__('New password')"
            autocomplete="new-password"
            :passwordrules="$viewModel->passwordRules()"
            :error="$errors->get('password')"
            allowPasswordManager
            required
            autofocus
          />

          <x-input
            type="password"
            id="password_confirmation"
            :label="__('Confirm password')"
            autocomplete="new-password"
            :passwordrules="$viewModel->passwordRules()"
            :error="$errors->get('password_confirmation')"
            allowPasswordManager
            required
          />
        </div>

        <p class="text-xs text-muted">{{ $viewModel->passwordRequirements() }}</p>
      </div>

      <x-button class="w-full">{{ __('Change my password') }}</x-button>
    </x-form>
  </x-box>
</x-auth-page>
