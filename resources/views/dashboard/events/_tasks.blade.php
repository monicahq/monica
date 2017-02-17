{{-- You added a task about Jane Doe --}}

{{ trans('dashboard.event_tasks_about') }}

<a href="/people/{{ $event['contact_id'] }}">{{ $event['contact_complete_name'] }}</a>
