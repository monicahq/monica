{{--
  Where somebody who followed a reset link chooses a new password.

  @var \App\ViewModels\Auth\NewPasswordViewModel $viewModel
--}}
<x-guest-layout :title="__('Choose a new password')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col justify-center px-6 py-10 sm:px-15">
      <div class="mx-auto w-full max-w-md space-y-6">
        <div class="flex items-center gap-3">
          <x-logo :size="30" />

          <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Choose a new password') }}</h1>

          <x-theme-toggle class="ml-auto" />
        </div>

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
      </div>

      <p class="mt-auto pt-10 text-center text-xs text-muted-soft">
        &copy; {{ config('app.name') }} {{ now()->format('Y') }} &middot; {{ __('Open source HR') }}
      </p>
    </main>

    @include('app.auth._quote', ['quote' => $viewModel->quote()])
  </div>
</x-guest-layout>
