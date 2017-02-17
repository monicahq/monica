<div class="section notes">

  <div class="section-heading">
    <img src="/img/people/dashboard/notes/notes.svg">
    {{ trans('people.notes_title') }}

    @if ($contact->getNumberOfNotes() != 0)
      <div class="section-action">
        <a href="/people/{{ $contact->id }}/note/add">{{ trans('people.notes_add_one_more') }}</a>
      </div>
    @endif
  </div>

  @if ($contact->getNumberOfNotes() == 0)

    {{-- Blank state --}}
    <div class="section-blank">
      <p>
        <a href="/people/{{ $contact->id }}/note/add">{{ trans('people.notes_blank_link') }}</a> {{ trans('people.notes_blank_name', ['name' => $contact->getFirstName() ]) }}.
      </p>
    </div>

  @else

    @foreach ($contact->getNotes() as $note)
    <div class="note-item">
      <div class="note-item-body">
        {{ $note->getBody() }}
      </div>

      <ul class="note-item-actions horizontal">
        <li class="note-item-time">{{ trans('people.notes_written_on', ['date' => $note->getCreatedAt(Auth::user()->locale)]) }}</li>
        <li class="note-item-link"><a href="/people/{{ $contact->id }}/notes/{{ $note->id }}/delete" class="action-link">{{ trans('app.delete') }}</a></li>
      </ul>
    </div>
    @endforeach

  @endif

</div>
