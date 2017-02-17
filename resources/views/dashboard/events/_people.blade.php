{{-- You added Jane Doe as a contact --}}

<a href="/people/{{ $event['contact_id'] }}">{{ $event['contact_complete_name'] }}</a>

{{ trans('dashboard.event_as_contact') }}
