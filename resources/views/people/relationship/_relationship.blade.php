@foreach ($relationships as $relationship)
  <div class="sidebar-box-paragraph">
    <span class="silver fw3 ba br2 ph1 {{ htmldir() == 'ltr' ? '' : 'fr' }}">{{ $relationship->relationshipType->getLocalizedName(null, false, $relationship->ofContact->gender->name) }}</span>

    {{-- NAME --}}
    @if ($relationship->ofContact->is_partial)
    <span class="{{ htmldir() == 'ltr' ? '' : 'fr' }}">{{ $relationship->ofContact->name }}</span>
    @else
    <a class="{{ htmldir() == 'ltr' ? '' : 'fr' }}" href="{{ route('people.show', $relationship->ofContact) }}">{{ $relationship->ofContact->name }}</a>
    @endif

    {{-- AGE --}}
    @if ($relationship->ofContact->birthday_special_date_id)
      @if ($relationship->ofContact->birthdate->getAge())
        <span class="{{ htmldir() == 'ltr' ? '' : 'fr' }}">({{ $relationship->ofContact->birthdate->getAge() }})</span>
      @endif
    @endif

    {{-- ACTIONS: EDIT/DELETE --}}
    @if ($relationship->ofContact->is_partial)
    <a href="{{ route('people.relationships.edit', [$contact, $relationship->ofContact]) }}" class="action-link {{ $contact->hashID() }}-edit-relationship">
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

    <form method="POST" action="{{ route('people.relationships.update', [$contact, $relationship->ofContact]) }}" class="entry-delete-form hidden">
      {{ method_field('DELETE') }}
      {{ csrf_field() }}
    </form>
  </div>
  <div class="cb"></div>
@endforeach
