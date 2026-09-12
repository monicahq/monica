{{-- The wall a sensitive action meets when the session has not lately proved whose it is. --}}
{{-- @var \App\ViewModels\Auth\ConfirmPasswordViewModel $viewModel --}}
<x-guest-layout :title="__('Confirm it is you')">
  <div class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 py-10">
    <div class="space-y-6">
      <div class="space-y-2">
        <div class="flex items-center gap-3">
          <x-logo :size="30" />

          <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Confirm it is you') }}</h1>
        </div>

        <p class="text-sm text-pretty text-body">{{ __('This setting controls your way back in. Prove it’s you first, and we’ll leave you alone for a while after.') }}</p>
      </div>

      @if ($viewModel->offersNothing())
        <x-box padding="p-4" class="rounded-lg">
          <div class="space-y-3">
            <x-notice tone="warning" icon="lock">{{ __('This account holds neither a password nor a passkey, so there is nothing to confirm with.') }}</x-notice>

            <p class="text-sm text-pretty text-body">{{ __('Ask for a link to choose a password, sign in with it, and this change is yours to make again.') }}</p>

            <x-button :href="route('auth.password.new')" data-turbo="true" class="w-full">{{ __('Choose a new password') }}</x-button>
          </div>
        </x-box>
      @endif

      @if ($viewModel->offersPassword())
        <x-form method="post" :action="route('auth.confirmPassword.create')">
          <x-box padding="p-4" class="rounded-lg">
            <div class="space-y-4">
              <x-input
                type="password"
                id="password"
                name="password"
                :label="__('Your password')"
                autocomplete="current-password"
                autofocus
                :error="$errors->first('password')"
                required
              />

              <x-button class="w-full">{{ __('Confirm') }}</x-button>
            </div>
          </x-box>
        </x-form>
      @endif

      @if ($viewModel->offersPasskey())
        <x-box
          padding="p-4"
          class="rounded-lg"
          x-data="passkey({{ \Illuminate\Support\Js::from([
            'routes' => [
              'options' => route('passkey.confirm-options'),
              'submit' => route('passkey.confirm'),
            ],
            'text' => [
              'cancelled' => __('Nothing was confirmed. The prompt was dismissed.'),
              'alreadyRegistered' => __('That passkey cannot be used here.'),
              'failed' => __('That did not work. Try again, or use your password.'),
            ],
          ]) }})"
          @passkey-confirmed="window.location = $event.detail.redirect"
        >
          <div x-cloak x-show="supported" class="flex flex-col gap-2.5">
            <x-button.secondary
              type="button"
              class="w-full"
              x-bind:disabled="busy"
              @click="confirm()"
            >{{ __('Use a passkey') }}</x-button.secondary>

            <p
              x-text="message"
              role="status"
              aria-live="polite"
              class="text-center text-sm empty:hidden"
              x-bind:class="failed ? 'text-error' : 'text-body'"
            ></p>
          </div>
        </x-box>
      @endif

      <x-auth-footnote :href="route('settings.security.index')" :label="__('Back to your security settings')"></x-auth-footnote>
    </div>
  </div>
</x-guest-layout>
