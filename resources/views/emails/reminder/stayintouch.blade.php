{{ trans('mail.greetings', ['username' => $user->first_name]) }},

{{ trans('mail.stay_in_touch_subject_description', ['name' => $contact->getCompleteName($user->name_order), 'frequency' => $contact->stay_in_touch_frequency]) }}

-------

{{ trans('mail.footer_contact_info') }}
{{ config('app.url') }}/people/{{ $contact->hashID() }}
