<x-mail::message>
# {{ __('Your password was removed') }}

{{ __('Your account no longer has a password. Your passkeys, and a link sent to this address, are what sign you in now.') }}

<x-mail::panel>
{{ __('If you did not remove it, set a password again from your security settings straight away.') }}
</x-mail::panel>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
