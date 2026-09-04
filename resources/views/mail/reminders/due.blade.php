{{--
  @var string $reminderName
  @var string $recordName
  @var string $sourceDate
  @var string $whenItIsAbout
--}}
<x-mail::message>
# {{ $reminderName }}

{{ $whenItIsAbout }}

{{ __('It is about :name, and the date it reads is :date.', ['name' => $recordName, 'date' => $sourceDate]) }}

{{ __('You asked to be told this when you added the reminder. You can change it, or stop it, wherever that date is written down.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
