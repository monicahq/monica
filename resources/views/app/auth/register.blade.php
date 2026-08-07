{{--
  Where somebody creates a company and the account that administers it.

  @var \App\ViewModels\Auth\RegisterViewModel $viewModel
--}}
@php
  $linkClasses = 'font-medium text-ink underline underline-offset-2 hover:text-brand';
  $terms = '<a href="'.e($viewModel->termsUrl()).'" target="_blank" rel="noopener" class="'.$linkClasses.'">'.e(__('terms of use')).'</a>';
  $privacy = '<a href="'.e($viewModel->privacyUrl()).'" target="_blank" rel="noopener" class="'.$linkClasses.'">'.e(__('privacy policy')).'</a>';
@endphp

<x-guest-layout :title="__('Create your account')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col px-6 pt-10 pb-8 sm:px-15 sm:pt-14">
      <div class="mx-auto w-full max-w-md space-y-6">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <x-logo :size="30" />

            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Create your account') }}</h1>

            <x-theme-toggle class="ml-auto" />
          </div>

          <p class="text-sm text-body">{{ __('You will be the first administrator of this company.') }}</p>
        </div>

        <x-status :message="session('status')" />

        {{-- The submit button stays greyed out until the terms are ticked. The
             server refuses the form all the same, so a browser without javascript
             still gets a working page and a clear error. --}}
        <div x-data="{ terms: @js((bool) old('terms')) }">
          <x-box>
            <x-form method="post" :action="route('auth.register.create')" class="space-y-4">
              <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-input
                  id="first_name"
                  :label="__('First name')"
                  :value="old('first_name')"
                  placeholder="John"
                  autocomplete="given-name"
                  :error="$errors->get('first_name')"
                  allowPasswordManager
                  required
                  autofocus
                />

                <x-input
                  id="last_name"
                  :label="__('Last name')"
                  :value="old('last_name')"
                  placeholder="Doe"
                  autocomplete="family-name"
                  :error="$errors->get('last_name')"
                  allowPasswordManager
                  required
                />
              </div>

              <x-input
                id="company_name"
                :label="__('Company name')"
                :value="old('company_name')"
                placeholder="Dunder Mifflin"
                autocomplete="organization"
                :help="__('You can rename it later.')"
                :error="$errors->get('company_name')"
                allowPasswordManager
                required
              />

              <x-input
                type="email"
                id="email"
                :label="__('Email address')"
                :value="old('email')"
                placeholder="john@doe.com"
                autocomplete="username"
                :help="__('We will never, ever send you marketing emails.')"
                :error="$errors->get('email')"
                allowPasswordManager
                required
              />

              {{-- new-password on both, and passwordrules stating the floor the server
                   actually enforces, so a password manager offers to generate one that
                   will pass rather than filling an existing password in. --}}
              <div class="space-y-1.5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <x-input
                    type="password"
                    id="password"
                    :label="__('Password')"
                    autocomplete="new-password"
                    passwordrules="minlength: 8"
                    :error="$errors->get('password')"
                    allowPasswordManager
                    required
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

              <x-checkbox id="terms" x-model="terms" :checked="(bool) old('terms')" :error="$errors->get('terms')" required>
                {!! __('I agree with the :terms and the :privacy.', ['terms' => $terms, 'privacy' => $privacy]) !!}
              </x-checkbox>

              <x-turnstile data-size="flexible" />

              <x-button class="w-full" x-bind:disabled="! terms">{{ __('Next step: validate your email address') }}</x-button>
            </x-form>
          </x-box>
        </div>

        <x-box padding="p-4" class="rounded-lg text-center text-sm text-body">
          {{ __('Already have an account?') }}
          <x-link turbo :href="route('auth.login.new')" class="font-semibold text-ink">{{ __('Sign in instead') }}</x-link>
        </x-box>

        <x-notice>
          {{ __('Joining a company that already uses OfficeLife? Ask an administrator to invite you, rather than creating a second account.') }}
        </x-notice>

        <div class="flex items-center gap-3">
          <x-language-picker :locales="$viewModel->locales()" :current="$viewModel->currentLocale()" />

          <span class="text-xs text-muted-soft">{{ __('You can change it any time.') }}</span>
        </div>
      </div>

      <p class="mt-auto pt-10 text-center text-xs text-muted-soft">
        &copy; {{ config('app.name') }} {{ now()->format('Y') }} &middot; {{ __('Open source HR') }}
      </p>
    </main>

    @include('app.auth._quote', ['quote' => $viewModel->quote()])
  </div>
</x-guest-layout>
