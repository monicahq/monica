@component('mail::message')
# @lang('Please join Monica')

@lang(':UserName invites you to join Monica, an open source personal CRM, designed to help you document your relationships.', ['userName' => $userName])

@component('mail::button', ['url' => $url])
@lang('Accept invitation and create your account')
@endcomponent

@lang('Thanks,')<br>
{{ config('app.name') }}
@endcomponent
