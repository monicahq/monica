{{-- Shown after asking for a sign-in link, whether or not the address had an account behind it. --}}
{{-- @var \App\ViewModels\Auth\MagicLinkViewModel $viewModel --}}
<x-auth-page
  :title="__('Check your inbox')"
  :description="trans_choice('If that address has an account, a link is on its way. It works once, and only for the next :count minute.|If that address has an account, a link is on its way. It works once, and only for the next :count minutes.', $viewModel->minutes())"
  :quote="$viewModel->quote()"
>
  <x-notice>
    {{ __('Nothing in your inbox? It can take a minute, and it sometimes arrives in the spam folder.') }}
  </x-notice>

  <x-auth-footnote :href="route('auth.login.new')" :label="__('Back to sign in')">
    {{ __('Sign in with your password instead') }}
  </x-auth-footnote>
</x-auth-page>
