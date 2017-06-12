{{-- You added Jane Doe as Regis's significant other --}}

{{ $event['object']->getName() }}

{{ trans('dashboard.event_as') }}

<a href="/people/{{ $event['contact_id'] }}">{{ trans('dashboard.event_significantother', ['name' => $event['contact_complete_name']]) }}</a>
