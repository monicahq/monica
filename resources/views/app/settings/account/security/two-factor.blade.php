{{-- Setting two factor up: pair an authenticator app, then type back what it is showing. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\TwoFactorEnrolmentViewModel $viewModel --}}
<x-settings.page
  :title="__('Two-factor authentication')"
  :user="$viewModel->user()"
  current="security"
  :back-href="route('settings.security.index')"
  :back-label="__('Back to security')"
>
  <x-page-header :title="__('Turn on two-factor authentication')" :description="__('Two steps, and you are done. Nothing changes on your account until the code below is accepted, so you cannot lock yourself out here.')" />

  <x-page-section :title="__('1. Pair your authenticator app')">
    <x-panel>
      <div class="grid gap-6 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
        <div class="flex flex-col gap-3">
          <p class="text-sm text-pretty text-muted">{{ __('Open the authenticator app on your phone, add an account, and point its camera at this square.') }}</p>

          <p class="text-sm text-pretty text-muted">{{ __('It files the account under :email.', ['email' => $viewModel->email()]) }}</p>

          <div class="flex flex-col gap-1.5 pt-1">
            <p class="text-xs text-pretty text-muted">{{ __('No camera? Type this into the app instead.') }}</p>

            <x-code-value class="tracking-wider">{{ $viewModel->secret() }}</x-code-value>
          </div>
        </div>

        <div class="mx-auto rounded-lg border border-hairline bg-white p-3">
          {{ $viewModel->qrCode() }}
        </div>
      </div>
    </x-panel>
  </x-page-section>

  <x-page-section :title="__('2. Prove it worked')" :description="__('The six digits change every thirty seconds.')">
    <x-form method="post" :action="route('settings.twoFactor.create')">
      <x-panel>
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

        <x-slot:footer>
          <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <x-button.secondary size="small" :href="route('settings.security.index')" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

            <x-button size="small">{{ __('Turn it on') }}</x-button>
          </div>
        </x-slot:footer>
      </x-panel>
    </x-form>
  </x-page-section>
</x-settings.page>
