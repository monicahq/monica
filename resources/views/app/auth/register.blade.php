{{-- Where somebody creates an account and the first user in it. --}}
{{-- @var \App\ViewModels\Auth\RegisterViewModel $viewModel --}}
@php
  $linkClasses = 'font-medium text-ink underline underline-offset-2 hover:decoration-ink';
  $terms = '<a href="'.e($viewModel->termsUrl()).'" target="_blank" rel="noopener" class="'.$linkClasses.'">'.e(__('terms of use')).'</a>';
  $privacy = '<a href="'.e($viewModel->privacyUrl()).'" target="_blank" rel="noopener" class="'.$linkClasses.'">'.e(__('privacy policy')).'</a>';
  $method = $viewModel->registrationMethod();
@endphp

<x-auth-page
  :title="__('Create your account')"
>
  <x-status :message="session('status')" />

  <div x-data="{
    terms: @js((bool) old('terms')),
    mode: {{ \Illuminate\Support\Js::from($method) }},
    supported: typeof globalThis.PublicKeyCredential === 'function',
  }" x-init="supported || (mode = 'password')">
    <x-box>
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

        @if ($viewModel->passkeysAreAvailable())
          <p class="text-sm text-pretty text-muted" @if ($method === 'password') x-cloak @endif x-show="mode === 'passkey'">{{ __('You will sign in with a passkey, unlocked by your fingerprint, your face or your device PIN. Nothing to remember, nothing to type.') }}</p>
        @endif

        <div @if ($method === 'passkey') x-cloak @endif x-show="mode === 'password'" class="space-y-1.5">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <x-input
              type="password"
              id="password"
              :label="__('Password')"
              autocomplete="new-password"
              :passwordrules="$viewModel->passwordRules()"
              :error="$errors->get('password')"
              allowPasswordManager
              required
              x-bind:required="mode === 'password'"
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
              x-bind:required="mode === 'password'"
            />
          </div>

          <p class="text-xs text-muted">{{ $viewModel->passwordRequirements() }}</p>
        </div>

        <input type="hidden" name="registration_method" value="password" x-bind:value="mode">

        <input type="hidden" name="timezone" x-data x-init="$el.value = Intl.DateTimeFormat().resolvedOptions().timeZone ?? ''">

        <x-checkbox id="terms" x-model="terms" :checked="(bool) old('terms')" :error="$errors->get('terms')" required>
          {!! __('I agree with the :terms and the :privacy.', ['terms' => $terms, 'privacy' => $privacy]) !!}
        </x-checkbox>

        <x-turnstile data-size="flexible" />

        <x-button class="w-full" x-bind:disabled="! terms">
          <span @if ($method === 'password') x-cloak @endif x-show="mode === 'passkey'">{{ __('Create a passkey') }}</span>
          <span @if ($method === 'passkey') x-cloak @endif x-show="mode === 'password'">{{ __('Next step: validate your email address') }}</span>
        </x-button>

        @if ($viewModel->passkeysAreAvailable())
          <x-button.secondary
            type="button"
            class="w-full"
            x-cloak
            x-show="supported"
            @click="mode = mode === 'passkey' ? 'password' : 'passkey'"
          >
            <span class="inline-flex items-center gap-2" @if ($method === 'passkey') x-cloak @endif x-show="mode === 'password'"><x-icon name="key" :size="16" />{{ __('Use a passkey instead') }}</span>
            <span class="inline-flex items-center gap-2" @if ($method === 'password') x-cloak @endif x-show="mode === 'passkey'"><x-icon name="lock" :size="16" />{{ __('Set a password instead') }}</span>
          </x-button.secondary>
        @endif
      </x-form>
    </x-box>
  </div>

  <x-auth-footnote :href="route('auth.login.new')" :label="__('Sign in instead')">
    {{ __('Already have an account?') }}
  </x-auth-footnote>

  <x-slot:footer>
    <x-language-picker :locales="$viewModel->locales()" :current="$viewModel->currentLocale()" />
  </x-slot:footer>
</x-auth-page>
