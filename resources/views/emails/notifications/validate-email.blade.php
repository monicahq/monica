@component('mail::message')
# Please verify your email address

You've been invited to use this email address in Monica, an open source personal CRM, so we can use it to send you notifications.

@component('mail::button', ['url' => $url])
Verify this email address
@endcomponent

If you've received this invitation by mistake, please discard it.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
