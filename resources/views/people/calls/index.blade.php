<div class="col-xs-12 section-title {{ \App\Helpers\LocaleHelper::getDirection() }}">
  <img src="/img/people/calls/phone.svg" class="icon-section">
  <h3>
    {{ trans('people.call_title') }}

    <span class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fr' : 'fl' }}">
      <a href="#logCallModal" class="btn edit-information" data-toggle="modal">{{ trans('people.call_button') }}</a>
    </span>
  </h3>
</div>

@if ($contact->calls()->count() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.call_blank_title', ['name' => $contact->first_name]) }}</h3>
      <a href="#logCallModal" data-toggle="modal">{{ trans('people.call_button') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12">

    @foreach($contact->calls as $call)

      <div class="ba br2 b--black-10 br--top w-100 mb4">
        <div class="pa2">
          @if (is_null($call->content))
            {{ trans('people.call_blank_desc', ['name' => $contact->first_name]) }}
          @else
            {!! $call->parsed_content !!}
          @endif
        </div>
        <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
          <div class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fl' : 'fr' }} w-50">
            {{ \App\Helpers\DateHelper::getShortDate($call->called_at) }}
          </div>
          <div class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fl tr' : 'fr tl' }} w-50">
            <a href="#" onclick="if (confirm('{{ trans('people.call_delete_confirmation') }}')) { $(this).parent().find('.entry-delete-form').submit(); } return false;">
              <i class="fa fa-trash-o" aria-hidden="true"></i>
            </a>

            <form method="POST" action="{{ action('Contacts\\CallsController@destroy', compact('contact', 'call')) }}" class="entry-delete-form hidden">
              {{ method_field('DELETE') }}
              {{ csrf_field() }}
            </form>
          </div>
        </div>
      </div>

    @endforeach

  </div>

@endif
