<div class="sidebar-box kids">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.kids_sidebar_title') }}</strong>
  </p>

  @if($contact->kids->count() === 0)
    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.kids.add', $contact) }}">{{ trans('people.kids_blank_cta') }}</a>
    </p>
  @else
    <ul class="people-list">
      @foreach($contact->kids as $kid)
      <li>
        <span class="name">{{ $kid->first_name }}</span>

        @if (! is_null($kid->age))
          ({{ $kid->age }})
        @endif

        <a href="{{ route('people.kids.edit', [$contact, $kid]) }}" class="action-link">{{ trans('app.edit') }}</a>
        <a href="{{ route('people.kids.delete', [$contact, $kid]) }}" class="action-link" onclick="return confirm('{{ trans('people.kids_delete_confirmation') }}');">{{ trans('app.delete') }}</a>
      </li>
      @endforeach
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.kids.add', $contact) }}">{{ trans('people.kids_blank_cta') }}</a>
    </p>

  @endif

</div>
