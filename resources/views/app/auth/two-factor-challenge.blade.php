{{--
  The second step of signing in, for somebody who enrolled in two factor
  authentication.

  @var \App\ViewModels\Auth\TwoFactorViewModel $viewModel
--}}
<x-guest-layout :title="__('One more step')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col justify-center px-6 py-10 sm:px-15">
      <div class="mx-auto w-full max-w-md space-y-6">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <x-logo :size="30" />

            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('One more step') }}</h1>

            <x-theme-toggle class="ml-auto" />
          </div>

          <p class="text-sm text-body">{{ __('Open your authenticator app and type the code it is showing.') }}</p>
        </div>

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
      </div>

      <p class="mt-auto pt-10 text-center text-xs text-muted-soft">
        &copy; {{ config('app.name') }} {{ now()->format('Y') }} &middot; {{ __('Open source HR') }}
      </p>
    </main>

    @include('app.auth._quote', ['quote' => $viewModel->quote()])
  </div>
</x-guest-layout>
