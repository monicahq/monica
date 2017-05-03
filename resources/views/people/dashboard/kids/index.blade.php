<div class="sidebar-box">

  <p class="sidebar-box-title">
    <img src="/img/people/dashboard/kids/children.svg">
    <strong>{{ trans('people.kids_sidebar_title') }}</strong>
    <a href="/people/{{ $contact->id }}/kid/add">{{ trans('app.add') }}</a>
  </p>

  @if ($contact->getNumberOfKids() == 0)
    <p class="sidebar-box-paragraph">
      {{ trans('people.kids_blank') }}
    </p>
  @else
    @foreach($contact->getKids() as $kid)
    <p>
      {{ $kid->getFirstName() }}
      ({{ $kid->getAge() }})
      <a href="/people/{{ $contact->id }}/kid/{{ $kid->id }}/edit" class="action-link">{{ trans('app.edit') }}</a>
        <a href="/people/{{ $contact->id }}/kid/{{ $kid->id }}/delete" class="action-link" onclick="return confirm('{{ trans('people.kids_delete_confirmation') }}');">{{ trans('app.delete') }}</a>
    </p>
    @endforeach
  @endif

</div>
