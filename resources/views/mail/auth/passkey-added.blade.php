{{--
  @var string $name
--}}
<x-mail::message>
# {{ __('A passkey was added') }}

{{ __('The passkey called :name can now sign in to your account.', ['name' => $name]) }}

<x-mail::panel>
{{ __('If you did not add it, remove it from your security settings now, and change your password.') }}
</x-mail::panel>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
