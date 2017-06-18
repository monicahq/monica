{{-- You added Jane as a child of Jane Doe --}}

{{ $event['object']->first_name }},

<a href="/people/{{ $event['contact_id'] }}">{{ $event['contact_complete_name'] }}</a> {{ trans('dashboard.event_child') }}
