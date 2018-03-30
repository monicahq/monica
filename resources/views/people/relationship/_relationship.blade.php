@foreach ($relationships as $relationship)
  <div class="sidebar-box-paragraph">
    <span class="silver fw3 ba br2 ph1">{{ $relationship->relationshipType->getLocalizedName(null, false, $relationship->ofContact->gender->name) }}</span>

    {{-- NAME --}}
    @if ($relationship->ofContact->is_partial)
    <span>{{ $relationship->ofContact->getCompleteName(auth()->user()->name_order) }}</span>
    @else
    <a href="{{ route('people.show', $relationship->ofContact) }}">{{ $relationship->ofContact->getCompleteName(auth()->user()->name_order) }}</a>
    @endif

    {{-- AGE --}}
    @if ($relationship->ofContact->birthday_special_date_id)
      @if ($relationship->ofContact->birthdate->getAge())
        ({{ $relationship->ofContact->birthdate->getAge() }})
      @endif
    @endif

    {{-- ACTIONS: EDIT/DELETE --}}
    @if ($relationship->ofContact->is_partial)
    <a href="{{ route('people.relationships.edit', [$contact, $relationship->ofContact]) }}" class="action-link {{ $contact->id }}-edit-relationship">
      {{ trans('app.edit') }}
    </a>
    <a href="#" onclick="if (confirm('{{ trans('people.relationship_delete_confirmation') }}')) { $(this).closest('.sidebar-box-paragraph').find('.entry-delete-form').submit(); } return false;" class="action-link">
      {{ trans('app.delete') }}
    </a>
    @else
    <a href="#" onclick="if (confirm('{{ trans('people.relationship_unlink_confirmation') }}')) { $(this).closest('.sidebar-box-paragraph').find('.entry-delete-form').submit(); } return false;" class="action-link">
      {{ trans('app.delete') }}
    </a>
    @endif

    <form method="POST" action="/people/{{ $contact->id }}/relationships/{{ $relationship->ofContact->id }}" class="entry-delete-form hidden">
      {{ method_field('DELETE') }}
      {{ csrf_field() }}
    </form>
  </div>
@endforeach
