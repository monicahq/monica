<x-mail::message>
# {{ __('Failed sign-in attempt') }}

{{ __('Somebody tried to sign in to your account and gave the wrong password. If that was you, nothing is wrong: try again, or use a sign-in link instead.') }}

<x-mail::panel>
{{ __('If it was not you, change your password now. Somebody knows your email address and is guessing.') }}
</x-mail::panel>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
