{{-- What somebody can change about the way they sign in: password, two factor, API keys. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<x-settings.page
  :title="__('Security and access')"
  :user="$viewModel->user()"
  current="security"
>
  <x-modal.confirm-password
    :offers-password="$viewModel->credentials()->offersPassword()"
    :offers-passkey="$viewModel->credentials()->offersPasskey()"
  />

  <x-page-header :title="__('Security and access')" :description="__('How you sign in to your account, and what else may act as you.')" />

  <x-page-section :title="__('Password')" :description="__('Choose one you use nowhere else. A long password is better than a complicated one.')">
    @include('app.settings.account.security._change-password', ['viewModel' => $viewModel])

    @include('app.settings.account.security._remove-password', ['viewModel' => $viewModel])
  </x-page-section>

  <x-page-section :title="__('Outside providers')" :description="__('Sign in with an account you already have elsewhere. Connecting one adds a way in and takes none away.')">
    <x-panel :flush="true">
      @include('app.settings.account.security._sso', ['viewModel' => $viewModel])
    </x-panel>
  </x-page-section>

  <x-page-section :title="__('Passkeys')" :description="__('A passkey is kept on your device and is unlocked by your fingerprint, your face or your device PIN. Nothing is typed, so nothing can be phished.')">
    <x-notice tone="quiet">{{ __('Signing in with a passkey does not ask for the two-factor code, since the passkey already needs this device and your fingerprint, your face or your device PIN.') }}</x-notice>

    <x-panel :flush="true">
      @include('app.settings.account.security._passkeys', ['viewModel' => $viewModel])
    </x-panel>
  </x-page-section>

  <x-page-section :title="__('Two-factor authentication')" :description="__('A password can be guessed, reused or stolen. A code that changes every thirty seconds, on a phone you hold, cannot be taken from a distance.')">
    <x-notice tone="quiet">{{ __('Signing in with a passkey does not ask for the two-factor code, since the passkey already needs this device and your fingerprint, your face or your device PIN.') }}</x-notice>

    <x-panel :flush="true">
      @if ($viewModel->usesAuthenticatorApp())
        @include('app.settings.account.security._two-factor-on', ['viewModel' => $viewModel])
      @else
        @include('app.settings.account.security._two-factor-off')
      @endif

      @include('app.settings.account.security._security-keys', ['viewModel' => $viewModel])

      @if ($viewModel->usesTwoFactorAuthentication())
        @include('app.settings.account.security._recovery-codes', ['viewModel' => $viewModel])
      @endif
    </x-panel>
  </x-page-section>

  <x-page-section :title="__('Where you are signed in')" :description="__('Every device signed in to your account. Remove one you do not recognise.')">
    <x-panel :flush="true">
      @include('app.settings.account.security._sessions', ['viewModel' => $viewModel])

      @if ($viewModel->canSignOutOtherDevices())
        <x-slot:footer>
          @include('app.settings.account.security._sign-out-other-devices')
        </x-slot:footer>
      @endif
    </x-panel>
  </x-page-section>

  <x-page-section :title="__('API keys')" :description="__('An API key lets a script or another system act as you, with exactly the access you have.')">
    <x-panel :flush="true">
      @include('app.settings.account.security._api-keys', ['viewModel' => $viewModel])
    </x-panel>
  </x-page-section>

  @if ($viewModel->mcpIsOn() || $viewModel->assistantsNotice() !== null)
    <x-page-section :title="__('Connected assistants')" :description="__('An assistant you connected through OAuth acts as you on the MCP server, without a key of its own.')">
      @if ($viewModel->assistantsNotice() !== null)
        <x-notice tone="quiet">{{ $viewModel->assistantsNotice() }}</x-notice>
      @endif

      <x-panel :flush="true">
        @include('app.settings.account.security._connected-assistants', ['viewModel' => $viewModel])
      </x-panel>

      @if ($viewModel->mcpIsOn())
        <x-link :href="route('settings.mcpCalls.index')">{{ __('See every call assistants made') }}</x-link>
      @endif
    </x-page-section>
  @endif
</x-settings.page>
