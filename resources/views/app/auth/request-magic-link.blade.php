{{-- Where somebody asks for a link that signs them in without a password. --}}
{{-- @var \App\ViewModels\Auth\MagicLinkViewModel $viewModel --}}
<x-auth-page
  :title="__('Get a link to sign in')"
  :description="__('Give your email address and we will send you a link that signs you in.')"
  :quote="$viewModel->quote()"
>
  <x-box>
    <x-form method="post" :action="route('auth.magicLink.create')" class="space-y-4">
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
    {{ __('Sign in with your password instead') }}
  </x-auth-footnote>
</x-auth-page>
