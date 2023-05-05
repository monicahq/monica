@component('mail::message')
# @lang('Hi :name', ['name' => $name])

@lang('You wanted to be reminded of the following:')

{{ $reason }}

@lang('For:')

{{ $contactName }}

@lang('Have a great day,')<br>
{{ config('app.name') }}
@endcomponent
