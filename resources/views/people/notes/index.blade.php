<div class="col-xs-12 section-title">
  <img src="/img/people/notes.svg" class="icon-section">
  <h3>
    {{ trans('people.notes_title') }}

    <span>
      <a href="{{ route('people.notes.add', $contact) }}" class="btn">{{ trans('people.notes_add_one_more') }}</a>
    </span>
  </h3>
</div>

@if ($contact->notes->count() === 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <a href="{{ route('people.notes.add', $contact) }}">{{ trans('people.notes_blank_link') }}</a> {{ trans('people.notes_blank_name', ['name' => $contact->getFirstName() ]) }}.
    </div>
  </div>

@else

  <div class="col-xs-12">

    @foreach ($contact->notes as $note)
      <div class="ba br2 b--black-10 br--top w-100 mb4">
        <div class="pa2">
          {!! $note->getParsedBodyAttribute() !!}
        </div>
        <div class="pa2 cf bt b--black-10 br--bottom f7 lh-copy">
          <div class="fl w-50">
            {{ $note->getCreatedAt(Auth::user()->locale) }}
          </div>
          <div class="fl w-50 tr">
            <a href="{{ route('people.notes.edit', [$contact, $note]) }}">{{ trans('app.edit') }}</a>
            |
            <a href="{{ route('people.notes.delete', [$contact, $note]) }}" onclick="return confirm('{{ trans('people.notes_delete_confirmation') }}');">{{ trans('app.delete') }}</a>
          </div>
        </div>
      </div>
    @endforeach

  </div>

@endif
