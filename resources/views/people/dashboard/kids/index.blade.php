<div class="sidebar-box kids">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.kids_sidebar_title') }}</strong>
  </p>

  @if ($contact->getNumberOfKids() == 0)
    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/kids/add">{{ trans('people.kids_blank_cta') }}</a>
    </p>
  @else
    <ul class="people-list">
      @foreach($contact->getKids() as $kid)
      <li>
        <span class="name">{{ $kid->getFirstName() }}</span>

        @if (! is_null($kid->getAge()))
        ({{ $kid->getAge() }})
        @endif

        <a href="/people/{{ $contact->id }}/kids/{{ $kid->id }}/edit" class="action-link">{{ trans('app.edit') }}</a>
        <a href="/people/{{ $contact->id }}/kids/{{ $kid->id }}/delete" class="action-link" onclick="return confirm('{{ trans('people.kids_delete_confirmation') }}');">{{ trans('app.delete') }}</a>
      </li>
      @endforeach
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="/people/{{ $contact->id }}/kids/add">{{ trans('people.kids_blank_cta') }}</a>
    </p>

  @endif

</div>
