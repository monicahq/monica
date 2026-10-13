{{-- Where somebody who cannot remember their password asks for a way back in. --}}
{{-- @var \App\ViewModels\Auth\PasswordResetViewModel $viewModel --}}
<x-auth-page
  :title="__('Forgot your password?')"
  :description="__('Give your email address and we will send you a link to choose a new one.')"
>
  <x-status :message="session('status')" />

  <x-box>
    <x-form method="post" :action="route('auth.password.create')" class="space-y-4">
      <x-input
        type="email"
        id="email"
        :label="__('Email address')"
        :value="old('email')"
        placeholder="john@doe.com"
        autocomplete="username"
        :error="$errors->get('email')"
        allowPasswordManager
        required
        autofocus
      />

      <x-button class="w-full">{{ __('Email me a link') }}</x-button>
    </x-form>
  </x-box>

  <x-auth-footnote :href="route('auth.login.new')" :label="__('Back to sign in')">
    {{ __('Back to sign in') }}
  </x-auth-footnote>
</x-auth-page>
