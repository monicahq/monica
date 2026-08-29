{{--
  Where somebody creates an account and the first user in it.

  @var \App\ViewModels\Auth\RegisterViewModel $viewModel
--}}
@php
  $linkClasses = 'font-medium text-ink underline underline-offset-2 hover:decoration-ink';
  $terms = '<a href="'.e($viewModel->termsUrl()).'" target="_blank" rel="noopener" class="'.$linkClasses.'">'.e(__('terms of use')).'</a>';
  $privacy = '<a href="'.e($viewModel->privacyUrl()).'" target="_blank" rel="noopener" class="'.$linkClasses.'">'.e(__('privacy policy')).'</a>';
@endphp

<x-guest-layout :title="__('Create your account')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col justify-center px-6 py-10 sm:px-15">
      <div class="mx-auto my-auto w-full max-w-md space-y-6">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <x-logo :size="30" />

            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Create your account') }}</h1>

            <x-theme-toggle class="ml-auto" />
          </div>

          <p class="text-sm text-body">{{ __('An account holds one person, and everything in it is yours alone.') }}</p>

        </div>

        <x-status :message="session('status')" />

        {{-- The submit button stays greyed out until the terms are ticked. The
             server refuses the form all the same, so a browser without javascript
             still gets a working page and a clear error. --}}
        {{--
          Two routes through one form. A passkey is what the screen leads with,
          because it is what the account should end up with; a password is one
          link away for whoever wants it, or whoever is on a device that cannot
          make a passkey. The fields nobody should have to type twice, name and
          email, are shared, so switching route loses nothing.

          `mode` starts on the password route when a passkey cannot be made here,
          or when the last attempt came back with errors from that route.
        --}}
        <div x-data="{
          terms: @js((bool) old('terms')),
          mode: {{ \Illuminate\Support\Js::from(! $viewModel->passkeysAreAvailable() || $errors->has('password') ? 'password' : 'passkey') }},
        }">
          @if ($viewModel->passkeysAreAvailable())
            {{--
              What a passkey is, in one sentence, before the device is asked for
              anything. Said in terms of unlocking a phone rather than in terms of
              cryptography. Inside the component, because it reads `mode`.
            --}}
            <p class="mb-4 text-sm text-pretty text-muted" x-show="mode === 'passkey'">{{ __('You will sign in with a passkey, unlocked by your fingerprint, your face or your device PIN. Nothing to remember, nothing to type.') }}</p>
          @endif
          <x-box>
            @unless ($viewModel->passkeysAreAvailable())
              <x-notice tone="warning" class="mb-4">
                @if ($viewModel->passkeysUnavailableReason() === \App\Helpers\PasskeyAvailability::NOT_SECURE)
                  {{ __('Passkeys need a secure connection, so this account will be created with a password. Reach this instance over https to use one.') }}
                @else
                  {{ __('Passkeys are switched off on this instance, so this account will be created with a password.') }}
                @endif
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
                :help="__('We will never, ever send you marketing emails.')"
                :error="$errors->get('email')"
                allowPasswordManager
                required
              />

              <div x-cloak x-show="mode === 'password'" class="space-y-1.5">
              {{-- new-password on both, and passwordrules stating the floor the server
                   actually enforces, so a password manager offers to generate one that
                   will pass rather than filling an existing password in. --}}
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

              {{-- The browser is the only thing that knows where somebody is, and every
                   date in the application is written in that time zone afterwards. A
                   browser that will not say leaves the field empty, and the server falls
                   back to the time zone of the instance. --}}
              <input type="hidden" name="timezone" x-data x-init="$el.value = Intl.DateTimeFormat().resolvedOptions().timeZone ?? ''">

              <x-checkbox id="terms" x-model="terms" :checked="(bool) old('terms')" :error="$errors->get('terms')" required>
                {!! __('I agree with the :terms and the :privacy.', ['terms' => $terms, 'privacy' => $privacy]) !!}
              </x-checkbox>

              <x-turnstile data-size="flexible" />

              {{--
                "Create a passkey" is the platform's own wording, so the button and
                the prompt that follows it say the same thing.
              --}}
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

        <x-box padding="p-4" class="rounded-lg text-center text-sm text-body">
          {{ __('Already have an account?') }}
          <x-link turbo :href="route('auth.login.new')" class="font-semibold text-ink">{{ __('Sign in instead') }}</x-link>
        </x-box>

        <div class="flex items-center gap-3">
          <x-language-picker :locales="$viewModel->locales()" :current="$viewModel->currentLocale()" />

          <span class="text-xs text-muted-soft">{{ __('You can change it any time.') }}</span>
        </div>
      </div>

      <p class="pt-10 text-center text-xs text-muted-soft">
        &copy; {{ config('app.name') }} {{ now()->format('Y') }} &middot; {{ __('Open source HR') }}
      </p>
    </main>

    @include('app.auth._quote', ['quote' => $viewModel->quote()])
  </div>
</x-guest-layout>
