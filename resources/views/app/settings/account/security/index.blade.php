{{--
  What somebody can change about the way they sign in: their password, whether a
  code is asked for on top of it, and the API keys that let something other than
  a browser act as them.

  Each panel holds a section of its own, kept in a partial beside this file.

  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
<x-app-layout :title="__('Security and access')">
  <x-slot:sidebar>
    <x-settings.sidebar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" current="security" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Security and access') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Security and access')"
    :description="__('How you sign in to your account, and what else may act as you.')"
  />

  <x-box :title="__('Change password')">
    <x-slot:help>
      <x-help :title="__('Change password')">
        {{ __('We never store your password itself, only a hash of it, which is why we ask for the current one rather than showing it to you. Changing it is written to your logs, so an account you share by accident leaves a trace.') }}
      </x-help>
    </x-slot:help>

    @include('app.settings.account.security._change-password', ['viewModel' => $viewModel])
  </x-box>

  <x-box :title="__('Two factor authentication')">
    <x-slot:help>
      <x-help :title="__('Two factor authentication')">
        {{ __('A password can be guessed, reused or stolen. A code that changes every thirty seconds, on a phone in your pocket, cannot be any of those things from a distance. Turning this on means somebody who knows your password still cannot get in.') }}
      </x-help>
    </x-slot:help>

    @if ($viewModel->usesTwoFactorAuthentication())
      @include('app.settings.account.security._two-factor-on', ['viewModel' => $viewModel])
    @else
      @include('app.settings.account.security._two-factor-off')
    @endif
  </x-box>

  <x-box :title="__('API keys')" padding="p-0">
    <x-slot:help>
      <x-help :title="__('API keys')">
        {{ __('A key is a password for machines. It is shown once, when you make it, and only a hash of it is kept afterwards, so a key you mislay is replaced rather than looked up. Revoking one stops it working straight away.') }}
      </x-help>
    </x-slot:help>

    <x-slot:description>
      <p>{{ __('A key lets a script or another system act as you, with exactly the access you have.') }}</p>
    </x-slot:description>

    <x-slot:additionalInfo>
      <x-notice>{{ __('There is nothing to point a key at yet. The API that accepts them is still being built, and a key you make now works the moment it lands.') }}</x-notice>
    </x-slot:additionalInfo>

    @include('app.settings.account.security._api-keys', ['viewModel' => $viewModel])
  </x-box>
</x-app-layout>
