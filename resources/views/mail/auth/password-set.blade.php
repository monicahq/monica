<x-mail::message>
# {{ __('A password was set') }}

{{ __('Your account can now be signed in to with a password, as well as with any passkey you hold.') }}

<x-mail::panel>
{{ __('If you did not set it, somebody else may have reached your account. Remove it and change it from your security settings now.') }}
</x-mail::panel>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
