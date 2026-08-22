{{--
  @var string $listingName
  @var int $versionNumber
  @var string $reason
--}}
<x-mail::message>
# {{ __('Not this time') }}

{{ __('Version :version of :name has not been accepted into the marketplace.', ['version' => $versionNumber, 'name' => $listingName]) }}

<x-mail::panel>
{{ $reason }}
</x-mail::panel>

{{ __('You can work on it and submit a new version whenever you are ready. Nothing you built has been touched, and nothing has been removed from your account.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
