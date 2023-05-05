@component('mail::message')
# @lang('Please verify your email address')

@lang('You’ve been invited to use this email address in Monica, an open source personal CRM, so we can use it to send you notifications.')

@component('mail::button', ['url' => $url])
@lang('Verify this email address')
@endcomponent

@lang('If you’ve received this invitation by mistake, please discard it.')

@lang('Thanks,')<br>
{{ config('app.name') }}
@endcomponent
