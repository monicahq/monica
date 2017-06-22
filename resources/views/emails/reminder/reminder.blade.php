{{ trans('mail.greetings', ['username' => $user->first_name]) }},

{{ trans('mail.want_reminded_of') }}:

{{ $reminder->getTitle() }}

{{ trans('mail.for') }}

{{ $contact->getCompleteName() }}

{{-- COMMENTS --}}
@if (! is_null($reminder->getDescription()))
COMMENT:
{{ $reminder->getDescription() }}
@endif

-------

{{ trans('mail.footer_contact_info') }}
{{ config('app.url') }}/people/{{ $contact->id }}
