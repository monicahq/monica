@if ($modules->contains('key', 'love_relationships'))
<div class="ba b--near-white br2 bg-gray-monica mb3 f6">
  <div class="pa3">
    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">{{ trans('app.relationship_type_group_love') }}</h3>
      </div>
    </div>

    @include('people.relationship._relationship', ['relationships' => $loveRelationships])
  </div>

  <p class="mb0 pa2 pl3 bt b--near-white f6">
    <a href="{{ route('people.relationships.create', $contact) }}?type={{ $contact->account->getRelationshipTypeByType('partner')->id }}">{{ trans('app.add') }}</a>
  </p>
</div>
@endif

@if ($modules->contains('key', 'family_relationships'))
<div class="ba b--near-white br2 bg-gray-monica mb3 f6">
  <div class="pa3">
    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">{{ trans('app.relationship_type_group_family') }}</h3>
      </div>
    </div>

    @include('people.relationship._relationship', ['relationships' => $familyRelationships])
  </div>

  <p class="mb0 pa2 pl3 bt b--near-white f6">
    <a href="{{ route('people.relationships.create', $contact) }}?type={{ $contact->account->getRelationshipTypeByType('child')->id }}">{{ trans('app.add') }}</a>
  </p>
</div>
@endif

@if ($modules->contains('key', 'other_relationships'))
<div class="ba b--near-white br2 bg-gray-monica mb3 f6">
  <div class="pa3">
    <div class="w-100 dt">
      <div class="dtc">
        <h3 class="f6 ttu normal">{{ trans('app.relationship_type_group_other') }}</h3>
      </div>
    </div>

    @include('people.relationship._relationship', ['relationships' => $friendRelationships])

    @include('people.relationship._relationship', ['relationships' => $workRelationships])
  </div>

  <p class="mb0 pa2 pl3 bt b--near-white f6">
    <a href="{{ route('people.relationships.create', $contact) }}?type={{ $contact->account->getRelationshipTypeByType('friend')->id }}">{{ trans('app.add') }}</a>
  </p>
</div>
@endif
