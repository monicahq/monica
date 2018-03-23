@foreach ($relationships as $relationship)
  <div>
    <span class="silver fw3">[{{ $relationship->relationshipType->getLocalizedName(null, false, $relationship->withContact->gender->name) }}]</span>

    {{-- NAME --}}
    @if ($relationship->withContact->is_partial)
    <span>{{ $relationship->withContact->getCompleteName(auth()->user()->name_order) }}</span>
    @else
    <a href="{{ route('people.show', $relationship->withContact) }}">{{ $relationship->withContact->getCompleteName(auth()->user()->name_order) }}</a>
    @endif

    {{-- AGE --}}
    @if ($relationship->withContact->birthday_special_date_id)
      @if ($relationship->withContact->birthdate->getAge())
        ({{ $relationship->withContact->birthdate->getAge() }})
      @endif
    @endif

    {{-- ACTIONS: EDIT/DELETE --}}
    @if ($relationship->withContact->is_partial)
    <a href="{{ route('people.relationships.edit', [$contact, $relationship->withContact]) }}" class="action-link {{ $contact->id }}-edit-relationship">
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

    <form method="POST" action="/people/{{ $contact->id }}/relationships/{{ $relationship->withContact->id }}" class="entry-delete-form hidden">
      {{ method_field('DELETE') }}
      {{ csrf_field() }}
    </form>
  </div>
@endforeach
