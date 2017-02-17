{{-- You added Jane as a child of Jane Doe --}}

{{ App\Kid::findOrFail($event['object_id'])->getFirstName() }},

<a href="/people/{{ $event['contact_id'] }}">{{ $event['contact_complete_name'] }}</a> {{ trans('dashboard.event_child') }}
