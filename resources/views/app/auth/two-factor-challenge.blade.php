{{-- The second step of signing in, for somebody who enrolled in two factor authentication. --}}
{{-- @var \App\ViewModels\Auth\TwoFactorViewModel $viewModel --}}
<x-auth-page
  :title="__('One more step')"
  :description="__('Open your authenticator app and type the code it is showing.')"
  :quote="$viewModel->quote()"
>
  <x-box>
    <x-form method="post" :action="route('auth.twoFactor.create')" class="space-y-4">
      <x-input
        id="code"
        :label="__('Six digit code')"
        placeholder="123456"
        autocomplete="one-time-code"
        inputmode="numeric"
        :error="$errors->get('code')"
        required
        autofocus
      />

      <x-button class="w-full">{{ __('Verify') }}</x-button>
    </x-form>
  </x-box>

  <x-notice>
    {{ __('Lost your phone? Use one of the recovery codes you saved when you set this up. Each one works once.') }}
  </x-notice>
</x-auth-page>
