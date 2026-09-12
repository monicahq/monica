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
        type="email"
        id="email"
        :label="__('Email address')"
        :value="old('email', $viewModel->email())"
        autocomplete="username"
        :error="$errors->get('email')"
        allowPasswordManager
        required
      />

      <div class="space-y-1.5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <x-input
            type="password"
            id="password"
            :label="__('New password')"
            autocomplete="new-password"
            passwordrules="minlength: 8"
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
            passwordrules="minlength: 8"
            :error="$errors->get('password_confirmation')"
            allowPasswordManager
            required
          />
        </div>

        <p class="text-xs text-muted">{{ __('Minimum 8 characters.') }}</p>
      </div>

      <x-button class="w-full">{{ __('Change my password') }}</x-button>
    </x-form>
  </x-box>
</x-auth-page>
