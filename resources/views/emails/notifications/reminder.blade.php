@component('mail::message')
# Hi {{ $name }}

You wanted to be reminded of the following:

{{ $reason }}

For:

{{ $contactName }}

Have a great day,<br>
{{ config('app.name') }}
@endcomponent
