<div class="col-xs-12 section-title">
  <img src="/img/people/calls/phone.svg" class="icon-section">
  <h3>
    {{ trans('people.call_title') }}

    <span>
      <a href="#logCallModal" class="btn edit-information" data-toggle="modal">{{ trans('people.call_button') }}</a>
    </span>
  </h3>
</div>

@if ($contact->calls()->count() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.call_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="#logCallModal" data-toggle="modal">{{ trans('people.call_button') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 calls-list">

    <ul class="table">
      @foreach($contact->calls as $call)
      <li class="table-row">
        <div class="table-cell date">
          {{ \App\Helpers\DateHelper::getShortDate($call->called_at) }}
        </div>
        <div class="table-cell reason">
          @if (! is_null($call->content))
            {{ $call->content }}
          @else
            <span class="empty">{{ trans('people.call_empty_comment') }}</span>
          @endif
        </div>
        <div class="table-cell list-actions">
          <a href="/people/{{ $contact->id }}/call/{{ $call->id }}/delete" onclick="return confirm('{{ trans('people.call_delete_confirmation') }}')">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
          </a>
        </div>
      </li>
      @endforeach
    </ul>

  </div>

@endif
