<div class="col-12 section-title">
  <contact-gift
    hash="{{ $contact->hashID() }}"
    :contact-id="{{ $contact->id }}"
    :gifts-active-tab="'{{ auth()->user()->gifts_active_tab }}'"
    :family-contacts="{{ $familyRelationships->map(function ($familyRelationship) {
      return [
          'id' => $familyRelationship->ofContact->id,
          'name' => $familyRelationship->ofContact->first_name,
      ];
    }) }}"
    :reach-limit="{{ \Safe\json_encode($hasReachedAccountStorageLimit) }}"
  >
  </contact-gift>
</div>
