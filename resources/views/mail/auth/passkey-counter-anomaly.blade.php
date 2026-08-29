{{--
  @var string $name
--}}
<x-mail::message>
# {{ __('A passkey answered in a way it should not have') }}

{{ __('The passkey called :name was refused because it answered in a way a single device cannot. That can mean a copy of it exists somewhere else.', ['name' => $name]) }}

<x-mail::panel>
{{ __('Remove that passkey from your security settings, add a new one on a device you hold, and change your password.') }}
</x-mail::panel>

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
