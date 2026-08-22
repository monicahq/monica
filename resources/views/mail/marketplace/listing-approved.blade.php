{{--
  @var string $listingName
  @var int $versionNumber
  @var string $url
--}}
<x-mail::message>
# {{ __('It is in') }}

{{ __('Version :version of :name has been approved and is now in the marketplace. Anybody can install it from today.', ['version' => $versionNumber, 'name' => $listingName]) }}

<x-mail::button :url="$url">
{{ __('See it in the marketplace') }}
</x-mail::button>

{{ __('What people install is frozen at the version they install. When you publish a new one, everybody holding an older copy is told and decides for themselves whether to move.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
