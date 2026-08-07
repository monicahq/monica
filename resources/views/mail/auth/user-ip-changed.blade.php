{{--
  @var string $email
  @var string $ip
--}}
<x-mail::message>
# {{ __('A sign-in from a new place') }}

{{ __('Your account :email was just signed in to from an address we have not seen before.', ['email' => $email]) }}

<x-mail::panel>
{{ __('When: :time', ['time' => now()->toDayDateTimeString()]) }}

{{ __('Where: :ip', ['ip' => $ip]) }}
</x-mail::panel>

{{ __('Travelling, a VPN, a new phone or a browser update can all cause this, so it is often nothing. If you do not recognise it, change your password.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
