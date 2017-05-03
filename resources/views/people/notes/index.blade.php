<div class="col-xs-12 section-title">
  <img src="/img/people/notes.svg" class="icon-section">
  <h3>
    {{ trans('people.notes_title') }}

    <span>
      <a href="/people/{{ $contact->id }}/note/add">{{ trans('people.notes_add_one_more') }}</a>
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

    <table class="table table-sm table-hover">
      <thead>
        <tr>
          <th>Date added</th>
          <th>Description</th>
          <th class="actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($contact->getNotes() as $note)
          <tr>
            <td class="date">
              {{ $note->getCreatedAt(Auth::user()->locale) }}
            </td>
            <td>
              {{ $note->getBody() }}
            </td>
            <td class="actions">
              <ul class="horizontal">
                <li><a href="/people/{{ $contact->id }}/notes/{{ $note->id }}/delete">{{ trans('app.delete') }}</a></li>
              </ul>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

  </div>

@endif
