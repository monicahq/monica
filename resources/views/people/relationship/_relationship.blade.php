@foreach ($relationships as $relationship)
  @if (! $relationship->ofContact)
    @continue
  @endif
  <div class="sidebar-box-paragraph">
    <span class="silver fw3 ba br2 ph1 {{ htmldir() == 'ltr' ? '' : 'fr' }}">{{ $relationship->relationshipType->getLocalizedName(null, false, $relationship->ofContact->gender ? $relationship->ofContact->gender->type : null) }}</span>

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
    <a href="{{ route('people.relationships.edit', [$contact, $relationship]) }}" class="action-link {{ $contact->hashID() }}-edit-relationship">
      {{ trans('app.edit') }}
    </a>
    <form method="POST" action="{{ route('people.relationships.destroy', [$contact, $relationship]) }}">
      @method('DELETE')
      @csrf
      <confirm message="{{ trans('people.relationship_unlink_confirmation') }}" link-class="action-link">
        {{ trans('app.delete') }}
      </confirm>
    </form>
  </div>
  <div class="cb"></div>
@endforeach
