{{--
  @var string $name
--}}
<x-mail::message>
# {{ __('A passkey was removed') }}

{{ __('The passkey called :name can no longer sign in to your account.', ['name' => $name]) }}

<x-mail::panel>
{{ __('If you did not remove it, somebody else may have reached your account. Change your password now.') }}
</x-mail::panel>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
