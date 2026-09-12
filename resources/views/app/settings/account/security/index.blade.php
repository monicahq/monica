{{-- What somebody can change about the way they sign in: password, two factor, API keys. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<x-shell :title="__('Security and access')">
  <x-slot:breadcrumb>
    <x-button.bar :href="route('settings.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-modal.confirm-password
    :confirmed="$viewModel->passwordIsConfirmed()"
    :seconds-left="$viewModel->secondsBeforeTheConfirmationExpires()"
    :offers-password="$viewModel->credentials()->offersPassword()"
    :offers-passkey="$viewModel->credentials()->offersPasskey()"
  />

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="security" />
  </x-slot:sidebar>

  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Security and access') }}</h1>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('How you sign in to your account, and what else may act as you.') }}</p>
  </header>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('Password') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('Choose one you use nowhere else. A long password is better than a complicated one.') }}</p>
    </div>

    @include('app.settings.account.security._change-password', ['viewModel' => $viewModel])

    @include('app.settings.account.security._remove-password', ['viewModel' => $viewModel])
  </section>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('Passkeys') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('A passkey is kept on your device and is unlocked by your fingerprint, your face or your device PIN. Nothing is typed, so nothing can be phished.') }}</p>
    </div>

    <x-panel :flush="true">
      @include('app.settings.account.security._passkeys', ['viewModel' => $viewModel])
    </x-panel>
  </section>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('Two-factor authentication') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('A password can be guessed, reused or stolen. A code that changes every thirty seconds, on a phone you hold, cannot be taken from a distance.') }}</p>
    </div>

    <x-panel :flush="true">
      @if ($viewModel->usesTwoFactorAuthentication())
        @include('app.settings.account.security._two-factor-on', ['viewModel' => $viewModel])
      @else
        @include('app.settings.account.security._two-factor-off')
      @endif
    </x-panel>
  </section>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('API keys') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('An API key lets a script or another system act as you, with exactly the access you have.') }}</p>
    </div>

    <x-notice>{{ __('There is nothing to point a key at yet. The API that accepts them is still being built, and a key you make now will work as soon as it is ready.') }}</x-notice>

    <x-panel :flush="true">
      @include('app.settings.account.security._api-keys', ['viewModel' => $viewModel])
    </x-panel>
  </section>
</x-shell>
