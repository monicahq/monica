@component('mail::message')
# Please join Monica

{{ $userName }} invites you to join Monica, an open source personal CRM, designed to help you document your relationships.

@component('mail::button', ['url' => $url])
Accept invitation and create your account
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
