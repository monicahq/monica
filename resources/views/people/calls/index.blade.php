<div class="col-xs-12 section-title">
  <img src="/img/people/calls/phone.svg" class="icon-section">
  <h3>
    {{ trans('people.call_title') }}

    <span class="{{ htmldir() == 'ltr' ? 'fr' : 'fl' }}">
      <a href="#logCallModal" cy-name="add-call-button" class="btn edit-information" data-toggle="modal">{{ trans('people.call_button') }}</a>
    </span>
  </h3>
</div>

@if ($contact->calls()->count() == 0)

  <div class="col-xs-12" cy-name="calls-blank-state">
    <div class="section-blank">
      <h3>{{ trans('people.call_blank_title', ['name' => $contact->first_name]) }}</h3>
      <a href="#logCallModal" data-toggle="modal">{{ trans('people.call_button') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12">

    @foreach($contact->calls as $call)

      <div class="ba br2 b--black-10 br--top w-100 mb4">
        <div class="pa2" cy-name="call-body-{{ $call->id }}">
          @if (is_null($call->content))
            {{ trans('people.call_blank_desc', ['name' => $contact->first_name]) }}
          @else
            {!! $call->parsed_content !!}
          @endif
        </div>
        <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
          <div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-50">
            {{ \App\Helpers\DateHelper::getShortDate($call->called_at) }}
          </div>
          <div class="{{ htmldir() == 'ltr' ? 'fl tr' : 'fr tl' }} w-50">
            <a href="#" cy-name="edit-call-button-{{ $call->id }}" onclick="if (confirm('{{ trans('people.call_delete_confirmation') }}')) { $(this).parent().find('.entry-delete-form').submit(); } return false;">
              <i class="fa fa-trash-o" aria-hidden="true"></i>
            </a>

            <form method="POST" action="{{ route('people.call.delete', [$contact, $call]) }}" class="entry-delete-form hidden">
              {{ method_field('DELETE') }}
              {{ csrf_field() }}
            </form>
          </div>
        </div>
      </div>

    @endforeach

  </div>

@endif
