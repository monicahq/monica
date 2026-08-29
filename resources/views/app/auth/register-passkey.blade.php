{{--
  The step right after the account was made, where the device is asked for the
  credential this account is meant to sign in with.

  It exists because the order is forced: a passkey is created against an account
  that already exists. So by the time this screen is reached the account is real,
  and the person is signed in. If they walk away here, they have an account that
  only a link sent to their address reaches, which is why this screen says that
  out loud rather than leaving it to be discovered at the next sign in.

  @var \App\ViewModels\Auth\RegisterPasskeyViewModel $viewModel
--}}
<x-guest-layout :title="__('Create your passkey')">
  <div class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 py-10">
    <div class="space-y-6">
      <div class="space-y-2">
        <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('One last thing, :name', ['name' => $viewModel->firstName()]) }}</h1>

        <p class="text-sm text-pretty text-body">{{ __('Your account is created. Now make the passkey you will sign in with, unlocked by your fingerprint, your face or your device PIN.') }}</p>
      </div>

      @if ($viewModel->hasPasskeys())
        {{-- Confirmed rather than assumed: this is the only credential the account has. --}}
        <x-box padding="p-4" class="rounded-lg">
          <div class="space-y-3">
            <x-notice tone="success" icon="check-circle">{{ __('Your passkey is created. It is what signs you in from now on.') }}</x-notice>

            <x-button.secondary :href="route('auth.verification.notice')" data-turbo="true" class="w-full">{{ __('Continue') }}</x-button.secondary>
          </div>
        </x-box>
      @elseif (! $viewModel->passkeysAreAvailable())
        <x-box padding="p-4" class="rounded-lg">
          <div class="space-y-3">
            <x-notice tone="warning">
              @if ($viewModel->passkeysUnavailableReason() === \App\Helpers\PasskeyAvailability::NOT_SECURE)
                {{ __('Passkeys need a secure connection, and this one is not. Set a password instead, and add a passkey later over https.') }}
              @else
                {{ __('Passkeys are switched off on this instance. Set a password instead.') }}
              @endif
            </x-notice>

            <x-button.secondary :href="route('settings.security.index')" data-turbo="true" class="w-full">{{ __('Set a password') }}</x-button.secondary>
          </div>
        </x-box>
      @else
        <x-box
          padding="p-4"
          class="rounded-lg"
          x-data="passkey({{ \Illuminate\Support\Js::from([
            'optionsUrl' => route('webauthn.store.options'),
            'storeUrl' => route('webauthn.store'),
            'redirectUrl' => route('auth.registerPasskey.new'),
            'text' => [
              'defaultName' => __('This device'),
              'cancelled' => __('No passkey was created. Your account exists, but only a link sent to your email address reaches it. Make a passkey, or set a password instead.'),
              'alreadyRegistered' => __('This device already has a passkey here.'),
              'failed' => __('The passkey could not be created. Please try again, or set a password instead.'),
            ],
          ]) }})"
        >
          <div x-cloak x-show="supported" class="space-y-3">
            <x-button
              type="button"
              class="w-full"
              x-bind:disabled="busy"
              @click="register()"
            >{{ __('Create a passkey') }}</x-button>

            {{--
              Always in the DOM, empty when there is nothing to say. A live region
              that appears at the same moment as its text is not reliably
              announced.
            --}}
            <p
              x-text="message"
              role="status"
              aria-live="polite"
              class="text-center text-sm text-pretty empty:hidden"
              x-bind:class="failed ? 'text-error' : 'text-body'"
            ></p>

            <p class="text-center text-xs text-muted">
              <x-link turbo :href="route('settings.security.index')" class="font-semibold text-ink">{{ __('Set a password instead') }}</x-link>
            </p>
          </div>

          <div x-cloak x-show="! supported" class="space-y-3">
            <x-notice tone="warning">{{ __('This browser cannot create passkeys. Set a password instead, and add a passkey later from another browser.') }}</x-notice>

            <x-button.secondary :href="route('settings.security.index')" data-turbo="true" class="w-full">{{ __('Set a password') }}</x-button.secondary>
          </div>
        </x-box>
      @endif
    </div>
  </div>
</x-guest-layout>
