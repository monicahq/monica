<div class="col-xs-12 section-title">
  <img src="/img/people/notes.svg" class="icon-section">
  <h3>
    {{ trans('people.notes_title') }}

    <span>
      <a href="/people/{{ $contact->id }}/note/add" class="btn">{{ trans('people.notes_add_one_more') }}</a>
    </span>
  </h3>
</div>

@if ($contact->getNotes()->count() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <a href="/people/{{ $contact->id }}/note/add">{{ trans('people.notes_blank_link') }}</a> {{ trans('people.notes_blank_name', ['name' => $contact->getFirstName() ]) }}.
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
            <a href="/people/{{ $contact->id }}/notes/{{ $note->id }}/delete" onclick="return confirm('{{ trans('people.notes_delete_confirmation') }}');">{{ trans('app.delete') }}</a>
          </span>
        </li>
      @endforeach
    </ul>

  </div>

@endif
