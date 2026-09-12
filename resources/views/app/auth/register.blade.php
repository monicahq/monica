{{-- Where somebody creates an account and the first user in it. --}}
{{-- @var \App\ViewModels\Auth\RegisterViewModel $viewModel --}}
@php
  $linkClasses = 'font-medium text-ink underline underline-offset-2 hover:decoration-ink';
  $terms = '<a href="'.e($viewModel->termsUrl()).'" target="_blank" rel="noopener" class="'.$linkClasses.'">'.e(__('terms of use')).'</a>';
  $privacy = '<a href="'.e($viewModel->privacyUrl()).'" target="_blank" rel="noopener" class="'.$linkClasses.'">'.e(__('privacy policy')).'</a>';
@endphp

<x-auth-page
  :title="__('Create your account')"
  :description="__('An account holds one person, and everything in it is yours alone.')"
  :quote="$viewModel->quote()"
>
  <x-status :message="session('status')" />

  <div x-data="{
    terms: @js((bool) old('terms')),
    mode: {{ \Illuminate\Support\Js::from(! $viewModel->passkeysAreAvailable() || $errors->has('password') ? 'password' : 'passkey') }},
  }">
    @if ($viewModel->passkeysAreAvailable())
      <p class="mb-4 text-sm text-pretty text-muted" x-show="mode === 'passkey'">{{ __('You will sign in with a passkey, unlocked by your fingerprint, your face or your device PIN. Nothing to remember, nothing to type.') }}</p>
    @endif
    <x-box>
      @unless ($viewModel->passkeysAreAvailable())
        <x-notice tone="warning" class="mb-4">
          {{ __('Passkeys need a secure connection, so this account will be created with a password. Reach this instance over https to use one.') }}
        </x-notice>
      @endunless

      <x-form
        method="post"
        x-bind:action="mode === 'passkey' ? {{ \Illuminate\Support\Js::from(route('auth.registerPasskey.create')) }} : {{ \Illuminate\Support\Js::from(route('auth.register.create')) }}"
        :action="route('auth.register.create')"
        class="space-y-4"
      >
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
          type="email"
          id="email"
          :label="__('Email address')"
          :value="old('email')"
          placeholder="john@doe.com"
          autocomplete="username"
          :help="__('We never send marketing emails.')"
          :error="$errors->get('email')"
          allowPasswordManager
          required
        />

        <div x-cloak x-show="mode === 'password'" class="space-y-1.5">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <x-input
              type="password"
              id="password"
              :label="__('Password')"
              autocomplete="new-password"
              passwordrules="minlength: 8"
              :error="$errors->get('password')"
              allowPasswordManager
              x-bind:required="mode === 'password'"
            />

            <x-input
              type="password"
              id="password_confirmation"
              :label="__('Confirm password')"
              autocomplete="new-password"
              passwordrules="minlength: 8"
              :error="$errors->get('password_confirmation')"
              allowPasswordManager
              x-bind:required="mode === 'password'"
            />
          </div>

          <p class="text-xs text-muted">{{ __('Minimum 8 characters.') }}</p>
        </div>

        <input type="hidden" name="timezone" x-data x-init="$el.value = Intl.DateTimeFormat().resolvedOptions().timeZone ?? ''">

        <x-checkbox id="terms" x-model="terms" :checked="(bool) old('terms')" :error="$errors->get('terms')" required>
          {!! __('I agree with the :terms and the :privacy.', ['terms' => $terms, 'privacy' => $privacy]) !!}
        </x-checkbox>

        <x-turnstile data-size="flexible" />

        <x-button class="w-full" x-bind:disabled="! terms">
          <span x-show="mode === 'passkey'">{{ __('Create a passkey') }}</span>
          <span x-cloak x-show="mode === 'password'">{{ __('Next step: validate your email address') }}</span>
        </x-button>

        @if ($viewModel->passkeysAreAvailable())
          <p class="text-center text-xs text-muted">
            <button
              type="button"
              class="cursor-pointer font-semibold text-ink underline decoration-hairline-strong underline-offset-2"
              @click="mode = mode === 'passkey' ? 'password' : 'passkey'"
              x-text="mode === 'passkey' ? {{ \Illuminate\Support\Js::from(__('Other options')) }} : {{ \Illuminate\Support\Js::from(__('Use a passkey instead')) }}"
            ></button>
          </p>
        @endif
      </x-form>
    </x-box>
  </div>

  <x-auth-footnote :href="route('auth.login.new')" :label="__('Sign in instead')">
    {{ __('Already have an account?') }}
  </x-auth-footnote>

  <div class="flex items-center gap-3">
    <x-language-picker :locales="$viewModel->locales()" :current="$viewModel->currentLocale()" />

    <span class="text-xs text-muted-soft">{{ __('You can change it any time.') }}</span>
  </div>
</x-auth-page>
