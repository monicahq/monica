<div class="col-xs-12 section-title">
  <img src="/img/people/notes.svg" class="icon-section">
  <h3>
    {{ trans('people.notes_title') }}

    <span class="fr">
      <a href="{{ route('people.notes.add', $contact) }}" class="btn">{{ trans('people.notes_add_one_more') }}</a>
    </span>
  </h3>
</div>

<div class="col-xs-12 section-title">
  <contact-note v-bind:contact-id="{!! $contact->id !!}"></contact-note>
</div>
