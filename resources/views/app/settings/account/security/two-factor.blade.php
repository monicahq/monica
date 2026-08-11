{{--
  Setting up two factor authentication: pair an authenticator app with the
  account, then prove it worked by typing back what the app is showing.

  It moves onto the new shell with the security screen rather than after it,
  because "Turn it on" leads straight here: a flow that changes design halfway
  through is worse than either design.

  Nothing is protected until that code is accepted, so somebody who closes the
  tab here is left exactly as they were, and the screen says so where somebody
  would worry about it.

  @var \App\ViewModels\Settings\Account\Security\TwoFactorEnrolmentViewModel $viewModel
--}}
<x-shell :title="__('Two factor authentication')">
  <x-slot:bar>
    <a href="{{ route('settings.security.index') }}" data-turbo="true" class="erica-btn erica-btn--secondary erica-btn--sm">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to security') }}
    </a>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <header class="flex flex-col gap-2">
    <h1 class="erica-page-title">{{ __('Turn on two factor authentication') }}</h1>

    <p class="erica-lede max-w-[56ch]">{{ __('Two steps, and you are done. Nothing changes about your account until the code below is accepted, so you cannot lock yourself out here.') }}</p>
  </header>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="erica-section-title shrink-0">{{ __('1. Pair your authenticator app') }}</h2>
    </div>

    <x-panel>
      <div class="grid gap-6 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
        <div class="flex flex-col gap-3">
          <p class="erica-small">{{ __('Open the authenticator app on your phone, add an account, and point its camera at this square.') }}</p>

          <p class="erica-small">{{ __('It files the account under :email.', ['email' => $viewModel->email()]) }}</p>

          <div class="flex flex-col gap-1.5 pt-1">
            <p class="erica-caption">{{ __('No camera? Type this into the app instead.') }}</p>

            <p class="rounded-md border border-hairline bg-sunken px-3 py-2 font-mono text-sm tracking-wider break-all text-ink select-all">{{ $viewModel->secret() }}</p>
          </div>
        </div>

        {{-- The square has to stay black on white to be readable, so it carries its own background in either theme. --}}
        <div class="mx-auto rounded-lg border border-hairline bg-white p-3">
          {{ $viewModel->qrCode() }}
        </div>
      </div>
    </x-panel>
  </section>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="erica-section-title shrink-0">{{ __('2. Prove it worked') }}</h2>

      <p class="erica-small">{{ __('The six digits change every thirty seconds.') }}</p>
    </div>

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

        {{-- The pair stacks on a narrow screen, primary first, so the thumb lands on the one that goes through. --}}
        <x-slot:footer>
          <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <x-button.secondary size="small" :href="route('settings.security.index')" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

            <x-button size="small">{{ __('Turn it on') }}</x-button>
          </div>
        </x-slot:footer>
      </x-panel>
    </x-form>
  </section>
</x-shell>
