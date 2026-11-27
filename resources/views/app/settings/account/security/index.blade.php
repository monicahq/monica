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

  <x-page-header :title="__('Security and access')" />

  <x-page-section :title="__('Password')">
    @include('app.settings.account.security._change-password', ['viewModel' => $viewModel])

    @include('app.settings.account.security._remove-password', ['viewModel' => $viewModel])
  </x-page-section>

  <x-page-section :title="__('Outside providers')" :description="__('Sign in with an account you already have elsewhere.')">
    <x-panel :flush="true">
      @include('app.settings.account.security._sso', ['viewModel' => $viewModel])
    </x-panel>
  </x-page-section>

  <x-page-section :title="__('Passkeys')" :description="__('A passkey is kept on your device and is unlocked by your fingerprint, your face or your device PIN. Nothing is typed, so nothing can be phished.')">
    <x-panel :flush="true">
      @include('app.settings.account.security._passkeys', ['viewModel' => $viewModel])
    </x-panel>
  </x-page-section>

  <x-page-section :title="__('Two-factor authentication')" :description="__('A password can be guessed, reused or stolen. A code that changes every thirty seconds, on a phone you hold, cannot be taken from a distance.')">
    <x-slot:help>
      <x-help
        :label="__('Help about two-factor authentication')"
        :title="__('An app, a key, or both?')"
        guide="security.two-factor#use-a-security-key-instead-of-a-code"
      >{{ __('The app gives you a code to type, and a security key is something you touch instead, such as a USB key or the fingerprint reader of this device. Either one turns this on, and you can keep both. The recovery codes get you in if you lose them.') }}</x-help>
    </x-slot:help>

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

  @if ($viewModel->davIsOn())
    <x-page-section :title="__('Contacts and calendar applications')" :description="__('Add this address to the contacts or calendar application of a phone or a computer, with your email address and one of your API keys as the password. Your account password will not work.')">
      <x-slot:help>
        <x-help
          :label="__('Help about contacts and calendar applications')"
          :title="__('What does this address sync?')"
          guide="integrations.overview#your-phone-and-computer-carddav-and-caldav"
        >{{ __('Each vault appears as an address book, and contacts sync in both directions. Each vault also offers a calendar of its recurring dates, such as birthdays, which is read only.') }}</x-help>
      </x-slot:help>

      <x-panel :flush="true">
        @include('app.settings.account.security._dav-address', ['viewModel' => $viewModel])
      </x-panel>
    </x-page-section>
  @endif

  <x-page-section :title="__('Connected apps')" :description="$viewModel->connectionsDescription()">
    <x-slot:help>
      <x-help
        :label="__('Help about connected apps')"
        :title="__('What is a connected app?')"
        guide="security.assistants"
      >{{ $viewModel->connectionsHelp() }}</x-help>
    </x-slot:help>

    @if ($viewModel->connectionsNotice() !== null)
      <x-notice tone="quiet">{{ $viewModel->connectionsNotice() }}</x-notice>
    @endif

    <x-panel :flush="true">
      @include('app.settings.account.security._connected-apps', ['viewModel' => $viewModel])
    </x-panel>

    @if ($viewModel->mcpIsOn())
      <x-link :href="route('settings.mcpCalls.index')">{{ __('See every call assistants made') }}</x-link>
    @endif
  </x-page-section>
</x-settings.page>
