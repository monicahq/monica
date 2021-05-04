@foreach($relationships->groupByItemsProperty('relationshipTypeLocalized') as $type => $relationshipType)

  <div class="sidebar-box-title">
    <h3>{{ $type }}</h3>
  </div>

  @foreach ($relationshipType as $relationship)
    @if (! $relationship->ofContact)
      @continue
    @endif
    <div class="sidebar-box-paragraph">
      {{-- NAME --}}
      @if ($relationship->ofContact->is_partial)
        <span class="{{ htmldir() == 'ltr' ? '' : 'fr' }}">{{ $relationship->ofContact->name }}</span>
      @else
        <a class="{{ htmldir() == 'ltr' ? '' : 'fr' }}" href="{{ route('people.show', $relationship->ofContact) }}">{{ $relationship->ofContact->name }}</a>
      @endif

      {{-- AGE --}}
      @if ($relationship->ofContact->is_dead)
        @if ($relationship->ofContact->deceasedDate)
          <span class="{{ htmldir() == 'ltr' ? '' : 'fr' }}">({{ $relationship->ofContact->getAgeAtDeath() }})</span>
        @endif
      @elseif ($relationship->ofContact->birthday_special_date_id)
        @if ($relationship->ofContact->birthdate->getAge())
          <span class="{{ htmldir() == 'ltr' ? '' : 'fr' }}">({{ $relationship->ofContact->birthdate->getAge() }})</span>
        @endif
      @endif

      <div class="db mt1">
        {{-- ACTIONS: EDIT/DELETE --}}
        <a href="{{ route('people.relationships.edit', [$contact, $relationship]) }}" class="action-link {{ $contact->hashID() }}-edit-relationship">{{ trans('app.edit') }}</a>
        <form method="POST" action="{{ route('people.relationships.destroy', [$contact, $relationship]) }}" class="di">
          @method('DELETE')
          @csrf
          <confirm message="{{ trans('people.relationship_unlink_confirmation') }}" link-class="action-link">
            {{ trans('app.delete') }}
          </confirm>
        </form>
      </div>
    </div>
    <div class="cb"></div>
  @endforeach

@endforeach
