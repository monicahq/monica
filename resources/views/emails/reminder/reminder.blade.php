{{ trans('mail.greetings', ['username' => $user->first_name]) }},

{{ trans('mail.want_reminded_of') }}:

{!! $reminder->title !!}

{{ trans('mail.for') }}

{{ $contact->name }}

{{-- COMMENTS --}}
@if (! is_null($reminder->description))
COMMENT:
{!! $reminder->description !!}
@endif

-------

{{ trans('mail.footer_contact_info') }}
{{ config('app.url') }}/people/{{ $contact->hashID() }}
