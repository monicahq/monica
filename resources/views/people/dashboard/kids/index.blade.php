<div class="sidebar-box kids">

  <p class="sidebar-box-title">
    <strong>{{ trans('people.kids_sidebar_title') }}</strong>
  </p>

  @if($contact->getOffsprings()->count() === 0)
    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.kids.add', $contact) }}">{{ trans('people.kids_blank_cta') }}</a>
    </p>
  @else
    <ul class="people-list">
      @foreach($contact->getOffsprings() as $kid)
      <li>

        @if ($kid->is_kid)

          <span class="name">{{ $kid->getCompleteName() }}</span>

          @if (! is_null($kid->getAge()))
            ({{ $kid->getAge() }})
          @endif

          <a href="{{ route('people.kids.edit', [$contact, $kid]) }}" class="action-link">{{ trans('app.edit') }}</a>
          <a href="{{ route('people.kids.delete', [$contact, $kid]) }}" class="action-link" onclick="return confirm('{{ trans('people.kids_delete_confirmation') }}');">{{ trans('app.delete') }}</a>

        @else

          <a href="/people/{{ $kid->id }}"><span class="name">{{ $kid->getCompleteName() }}</span></a>

          @if (! is_null($kid->getAge()))
            ({{ $kid->getAge() }})
          @endif

          <a href="/people/{{ $contact->id }}/kids/{{ $kid->id }}/unlink" class="action-link" onclick="return confirm('{{ trans('people.kids_delete_confirmation') }}');">Remove</a>

        @endif

      </li>
      @endforeach
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.kids.add', $contact) }}">{{ trans('people.kids_blank_cta') }}</a>
    </p>

  @endif

</div>
