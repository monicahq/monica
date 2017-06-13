<div class="col-xs-12 section-title">
  <img src="{{ asset('/img/people/notes.svg') }}" class="icon-section">
  <h3>
    {{ trans('people.notes_title') }}

    <span>
      <a href="{{ route('people.notes.add', ['people' => $contact->id]) }}" class="btn">{{ trans('people.notes_add_one_more') }}</a>
    </span>
  </h3>
</div>

@if ($contact->getNotes()->count() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <a href="{{ route('people.notes.add', ['people' => $contact->id]) }}">{{ trans('people.notes_blank_link') }}</a> {{ trans('people.notes_blank_name', ['name' => $contact->getFirstName() ]) }}.
    </div>
  </div>

@else

  <div class="col-xs-12">

    <ul class="notes-list">
      @foreach ($contact->getNotes() as $note)
        <li>
          {{ $note->getBody() }}
          <span class="note-date">
            {{ $note->getCreatedAt(Auth::user()->locale) }}

            <a href="{{ route('people.notes.delete', ['people' => $contact->id, 'note' => $note->id]) }}" onclick="return confirm('{{ trans('people.notes_delete_confirmation') }}');">{{ trans('app.delete') }}</a>
          </span>
        </li>
      @endforeach
    </ul>

  </div>

@endif
