@php
    function cmp($a, $b)
    {
        return strcmp($a->relationshipType->getLocalizedName(null, false, $relationship->ofContact->gender ? $relationship->ofContact->gender->type : null), $b->relationshipType->getLocalizedName(null, false, $relationship->ofContact->gender ? $relationship->ofContact->gender->type : null));
    }
    usort($relationships, "cmp");
    $curr_relationship = NULL;
@endphp
@foreach ($relationships as $relationship)
  @if (! $relationship->ofContact)
    @continue
  @endif
  <div class="sidebar-box-paragraph">
    @php 
      $relationshipType = $relationship->relationshipType->getLocalizedName(null, false, $relationship->ofContact->gender ? $relationship->ofContact->gender->type : null);
      if( $curr_relationship !== $relationshipType )
      {
        echo "<h3>" . relationshipType . "</h3>";
        $curr_relationship = relationshipType;
      }
    @endphp

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
