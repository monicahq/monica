<div class="event-icon">
  <img src="/img/dashboard/call_{{ $event['nature_of_operation'] }}.png">
</div>

<div class="event-description">
  <a href="/people/{{ $event['contact_id'] }}" id="call_{{ $event['nature_of_operation'] }}_{{ $event['object_id'] }}">
    {{ trans('dashboard.event_'.$event['nature_of_operation'].'_'.$event['object_type'], ['name' => $event['contact_complete_name']]) }}
  </a>
</div>
