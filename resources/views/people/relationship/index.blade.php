@if ($modules->contains('key', 'love_relationships'))
<div class="sidebar-box">
  <div class="sidebar-box-title">
    <h3>{{ trans('app.relationship_type_group_love') }}</h3>
  </div>

  @include('people.relationship._relationship', ['relationships' => $loveRelationships])

  <p class="mb0 bt b--near-white f6">
    <a href="{{ route('people.relationships.create', $contact) }}?type={{ $contact->account->getRelationshipTypeByType('partner')->id }}">{{ trans('app.add') }}</a>
  </p>
</div>
@endif

@if ($modules->contains('key', 'family_relationships'))
<div class="sidebar-box">
  <div class="sidebar-box-title">
    <h3>{{ trans('app.relationship_type_group_family') }}</h3>
  </div>

  @include('people.relationship._relationship', ['relationships' => $familyRelationships])

  <p class="mb0 bt b--near-white f6">
    <a href="{{ route('people.relationships.create', $contact) }}?type={{ $contact->account->getRelationshipTypeByType('child')->id }}">{{ trans('app.add') }}</a>
  </p>
</div>
@endif

@if ($modules->contains('key', 'other_relationships'))
<div class="sidebar-box f6">
  <div class="sidebar-box-title">
    <h3>{{ trans('app.relationship_type_group_other') }}</h3>
  </div>

  @include('people.relationship._relationship', ['relationships' => $friendRelationships])

  @include('people.relationship._relationship', ['relationships' => $workRelationships])

  <p class="mb0 bt b--near-white f6">
    <a href="{{ route('people.relationships.create', $contact) }}?type={{ $contact->account->getRelationshipTypeByType('friend')->id }}">{{ trans('app.add') }}</a>
  </p>
</div>
@endif
