{{--
  @var string $url
--}}
<x-mail::message>
# {{ __('Confirm your email address') }}

{{ __('Welcome to OfficeLife. Click the button below to confirm this address, and your account is ready.') }}

<x-mail::button :url="$url">
{{ __('Confirm my email address') }}
</x-mail::button>

{{ __('If you did not create an account, you can safely ignore this email.') }}
</x-mail::message>
