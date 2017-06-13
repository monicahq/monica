<div class="sidebar-box kids">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.kids_sidebar_title') }}</strong>
  </p>

  @if ($contact->getNumberOfKids() == 0)
    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.dashboard.kid.add', ['people' => $contact->id]) }}">{{ trans('people.kids_blank_cta') }}</a>
    </p>
  @else
    <ul class="people-list">
      @foreach($contact->getKids() as $kid)
      <li>
        <span class="name">{{ $kid->getFirstName() }}</span>

        @if (! is_null($kid->getAge()))
        ({{ $kid->getAge() }})
        @endif



        <a href="{{ route('people.dashboard.kid.edit', ['people' => $contact->id, 'kid' => $kid->id]) }}" class="action-link">{{ trans('app.edit') }}</a>
        <a href="{{ route('people.dashboard.kid.delete', ['people' => $contact->id, 'kid' => $kid->id]) }}" class="action-link" onclick="return confirm('{{ trans('people.kids_delete_confirmation') }}');">{{ trans('app.delete') }}</a>
      </li>
      @endforeach
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.dashboard.kid.add', ['people' => $contact->id]) }}">{{ trans('people.kids_blank_cta') }}</a>
    </p>

  @endif

</div>
