{{-- The step right after signing up: go and click the link we just sent. --}}
{{-- @var \App\ViewModels\Auth\VerifyEmailViewModel $viewModel --}}
<x-auth-page
  :title="__('Confirm your email address')"
  :description="__('We sent a link to :email. Click it, and your account is ready.', ['email' => $viewModel->email()])"
>
  <x-status :message="session('status')" />

  <x-box class="space-y-4">
    <p class="text-sm leading-relaxed text-body">
      {{ __('Nothing in your inbox? It can take a minute, and it sometimes arrives in the spam folder.') }}
    </p>

    <x-form method="post" :action="route('auth.verification.send')">
      <x-button class="w-full">{{ __('Send the email again') }}</x-button>
    </x-form>
  </x-box>

  <x-notice>
    {{ __('You can close this page. The link works from any browser, on any device.') }}
  </x-notice>
</x-auth-page>
