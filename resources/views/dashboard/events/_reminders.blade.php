<a href="/people/{{ $event['contact_id'] }}">{{ trans('dashboard.event_reminder') }}</a>

{{-- about <a href="">Name</a> --}}
{{ trans('dashboard.event_about') }} <a href="/people/{{ $event['contact_id'] }}">{{ $event['contact_complete_name'] }}</a>
