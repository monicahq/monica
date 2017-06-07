{{-- You added a gift about Jane Doe --}}

{{ trans('dashboard.event_gift_about') }}

<a href="/people/{{ $event['contact_id'] }}">{{ $event['contact_complete_name'] }}</a>
