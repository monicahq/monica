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

        @if ($kid->is_partial)

          <span class="name">{{ $kid->getCompleteName(auth()->user()->name_order) }}</span>

          @if ($kid->birthday_special_date_id)
            @if ($kid->birthdate->getAge())
              ({{ $kid->birthdate->getAge() }})
            @endif
          @endif

          <a href="{{ route('people.kids.edit', [$contact, $kid]) }}" class="action-link">{{ trans('app.edit') }}</a>
          <a href="#" class="action-link" onclick="if (confirm('{{ trans('people.kids_delete_confirmation') }}')) { $(this).closest('li').find('.entry-delete-form').submit(); } return false;">{{ trans('app.delete') }}</a>

          <form method="POST" action="{{ route('people.kids.delete', [$contact, $kid]) }}" class="entry-delete-form hidden">
            {{ method_field('DELETE') }}
            {{ csrf_field() }}
          </form>

        @else

          <a href="/people/{{ $kid->id }}"><span class="name">{{ $kid->getCompleteName(auth()->user()->name_order) }}</span></a>

          @if ($kid->birthday_special_date_id)
            @if ($kid->birthdate->getAge())
              ({{ $kid->birthdate->getAge() }})
            @endif
          @endif

          <a href="#" class="action-link" onclick="if (confirm('{{ trans('people.kids_unlink_confirmation') }}')) { $(this).closest('li').find('.entry-delete-form').submit(); } return false;">{{ trans('app.remove') }}</a>

          <form method="POST" action="{{ action('Contacts\\KidsController@unlink', compact('contact', 'kid')) }}" class="entry-delete-form hidden">
            {{ csrf_field() }}
          </form>

        @endif

      </li>
      @endforeach
    </ul>

    <p class="sidebar-box-paragraph">
      <a href="{{ route('people.kids.add', $contact) }}">{{ trans('people.kids_blank_cta') }}</a>
    </p>

  @endif

</div>
