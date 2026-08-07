{{--
  Setting up two factor authentication: pair an authenticator app with the
  account, then prove it worked by typing back what the app is showing.

  Nothing is protected until that code is accepted, so somebody who closes the
  tab here is left exactly as they were.

  @var \App\ViewModels\Settings\Account\Security\TwoFactorEnrolmentViewModel $viewModel
--}}
<x-app-layout :title="__('Two factor authentication')">
  <x-slot:sidebar>
    <x-settings.sidebar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" current="security" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <x-link :href="route('settings.security.index')" turbo>{{ __('Security and access') }}</x-link>
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Two factor authentication') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Turn on two factor authentication')"
    :description="__('Two steps, and you are done.')"
  />

  <x-box :title="__('1. Pair your authenticator app')">
    <div class="grid gap-x-9 gap-y-5 md:grid-cols-[minmax(0,1fr)_auto]">
      <div class="space-y-2.5 text-sm leading-relaxed text-body">
        <p>{{ __('Open the authenticator app on your phone, add an account, and point its camera at this square.') }}</p>
        <p>{{ __('It files the account under :email.', ['email' => $viewModel->email()]) }}</p>

        <div class="space-y-1.5 pt-1.5">
          <p class="text-sm text-muted">{{ __('No camera? Type this into the app instead.') }}</p>

          <p class="rounded-md border border-hairline bg-sunken px-3 py-2 font-mono text-sm tracking-wider break-all text-ink select-all">{{ $viewModel->secret() }}</p>
        </div>
      </div>

      {{-- The square has to stay black on white to be readable, so it carries its own background in either theme. --}}
      <div class="mx-auto rounded-lg border border-hairline bg-white p-3">
        {{ $viewModel->qrCode() }}
      </div>
    </div>
  </x-box>

  <x-box :title="__('2. Prove it worked')">
    <div class="grid gap-x-9 gap-y-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
      <div class="space-y-2.5 text-sm leading-relaxed text-body">
        <p>{{ __('Type the six digits the app is showing right now. They change every thirty seconds.') }}</p>
        <p>{{ __('Nothing changes about your account until this code is accepted, so you cannot lock yourself out here.') }}</p>
      </div>

      <x-form method="post" :action="route('settings.twoFactor.create')" class="space-y-3.5">
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

        {{-- The pair stacks on a narrow screen, primary first, so the thumb lands on the one that goes through. --}}
        <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:justify-end">
          <x-button.secondary :href="route('settings.security.index')" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

          <x-button>{{ __('Turn it on') }}</x-button>
        </div>
      </x-form>
    </div>
  </x-box>
</x-app-layout>
