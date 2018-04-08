@if ($modules->contains('key', 'love_relationships'))
<div class="ba b--near-white br2 bg-gray-monica pa3 mb3 f6">
  <div class="w-100 dt">
    <div class="dtc">
      <h3 class="f6 ttu normal">{{ trans('app.relationship_type_group_'.auth()->user()->account->getRelationshipTypeGroupByType('love')->name) }}</h3>
    </div>
  </div>

   @include('people.relationship._relationship', ['relationships' => $loveRelationships])


  <p class="mb0">
    <a href="/people/{{ $contact->hashID() }}/relationships/new?type={{ $contact->account->getRelationshipTypeByType('partner')->id }}">{{ trans('app.add') }}</a>
  </p>
</div>
@endif

@if ($modules->contains('key', 'family_relationships'))
<div class="ba b--near-white br2 bg-gray-monica pa3 mb3 f6">
  <div class="w-100 dt">
    <div class="dtc">
      <h3 class="f6 ttu normal">{{ trans('app.relationship_type_group_'.auth()->user()->account->getRelationshipTypeGroupByType('family')->name) }}</h3>
    </div>
  </div>


   @include('people.relationship._relationship', ['relationships' => $familyRelationships])

   <p class="mb0">
    <a href="/people/{{ $contact->hashID() }}/relationships/new?type={{ $contact->account->getRelationshipTypeByType('child')->id }}">{{ trans('app.add') }}</a>
  </p>
</div>
@endif

@if ($modules->contains('key', 'other_relationships'))
<div class="ba b--near-white br2 bg-gray-monica pa3 mb3 f6">
  <div class="w-100 dt">
    <div class="dtc">
      <h3 class="f6 ttu normal">{{ trans('app.relationship_type_group_other') }}</h3>
    </div>
  </div>

   @include('people.relationship._relationship', ['relationships' => $friendRelationships])

   @include('people.relationship._relationship', ['relationships' => $workRelationships])

   <p class="mb0">
    <a href="/people/{{ $contact->hashID() }}/relationships/new?type={{ $contact->account->getRelationshipTypeByType('friend')->id }}">{{ trans('app.add') }}</a>
  </p>
</div>
@endif
