{{-- You added an activity about Jane Doe --}}

{{ trans('dashboard.event_activity_about') }}

<a href="/people/{{ $event['contact_id'] }}">{{ $event['contact_complete_name'] }}</a>
