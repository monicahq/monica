{{--
  The step right after signing up: go and click the link we just sent.

  @var \App\ViewModels\Auth\VerifyEmailViewModel $viewModel
--}}
<x-guest-layout :title="__('Confirm your email address')">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col justify-center px-6 py-10 sm:px-15">
      <div class="mx-auto w-full max-w-md space-y-6">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <x-logo :size="30" />

            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Confirm your email address') }}</h1>

            <x-theme-toggle class="ml-auto" />
          </div>

          <p class="text-sm text-body">
            {{ __('We sent a link to :email. Click it, and your account is ready.', ['email' => $viewModel->email()]) }}
          </p>
        </div>

        <x-status :message="session('status')" />

        <x-box class="space-y-4">
          <p class="text-sm leading-relaxed text-body">
            {{ __('Nothing in your inbox? It can take a minute, and it sometimes lands in the spam folder.') }}
          </p>

          <x-form method="post" :action="route('auth.verification.send')">
            <x-button class="w-full">{{ __('Send the email again') }}</x-button>
          </x-form>
        </x-box>

        <x-notice>
          {{ __('You can close this page. The link works from any browser, on any device.') }}
        </x-notice>
      </div>
    </main>

    @include('app.auth._quote', ['quote' => $viewModel->quote()])
  </div>
</x-guest-layout>
