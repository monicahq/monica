{{--
  @var string $ip
--}}
<x-mail::message>
# {{ __('You signed in without a password') }}

{{ __('Your account was just signed in to using a link sent by email, from :ip.', ['ip' => $ip]) }}

<x-mail::panel>
{{ __('If this was not you, change your password now. The link only works once and has already been used, but whoever used it can read your email.') }}
</x-mail::panel>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
