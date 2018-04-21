{{ trans('mail.greetings', ['username' => $user->first_name]) }},

You asked to be reminded to stay in touch with {{ $contact->getCompleteName($user->name_order) }} every {{ $contact->stay_in_touch_frequency }} days.

-------

{{ trans('mail.footer_contact_info') }}
{{ config('app.url') }}/people/{{ $contact->hashID() }}
