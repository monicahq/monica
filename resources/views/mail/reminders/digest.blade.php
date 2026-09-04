{{--
  @var string $heading
  @var array $lines
--}}
<x-mail::message>
# {{ $heading }}

@foreach($lines as $line)
**{{ $line['title'] }}**<br>
{{ $line['body'] }}

@endforeach

{{ __('You asked for one message a day rather than one at a time. You can change that wherever your notifications are set up.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
