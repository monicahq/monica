{{-- The second step of signing in, for somebody who enrolled in two factor authentication. --}}
{{-- @var \App\ViewModels\Auth\TwoFactorViewModel $viewModel --}}
<x-auth-page
  :title="__('One more step')"
  :description="$viewModel->description()"
>
  @if ($viewModel->offersKey())
    <div x-data="passkey({{ \Illuminate\Support\Js::from($viewModel->keyPrompt(openOnLoad: ! $errors->has('code'))) }})">
      <div x-show="supported" class="flex flex-col gap-2">
        <x-button type="button" class="w-full" x-bind:disabled="busy" @click="secondFactor()">
          <x-slot:icon><x-icon name="key" :size="16" /></x-slot:icon>
          {{ __('Use a security key or passkey') }}
        </x-button>

        <p
          x-text="message"
          role="status"
          aria-live="polite"
          class="text-center text-sm empty:hidden"
          x-bind:class="failed ? 'text-error' : 'text-body'"
        ></p>
      </div>

      <p x-cloak x-show="! supported" class="text-center text-sm text-body">
        {{ __('This browser cannot use a security key. Use another way below.') }}
      </p>
    </div>
  @endif

  <x-box>
    <x-form method="post" :action="route('auth.twoFactor.create')" class="space-y-4">
      <x-input
        id="code"
        :label="$viewModel->codeLabel()"
        :placeholder="$viewModel->usesAuthenticatorApp() ? '123456' : null"
        autocomplete="one-time-code"
        :inputmode="$viewModel->usesAuthenticatorApp() ? 'numeric' : null"
        :error="$errors->get('code')"
        required
        :autofocus="! $viewModel->offersKey() || $errors->has('code')"
      />

      <x-button class="w-full">{{ __('Verify') }}</x-button>
    </x-form>
  </x-box>

  <x-notice>
    {{ $viewModel->notice() }}
  </x-notice>
</x-auth-page>
