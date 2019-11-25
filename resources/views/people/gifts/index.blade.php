<div class="col-12 section-title">
  <contact-gift hash="{{ $contact->hashID() }}" :gifts-active-tab="'{{ auth()->user()->gifts_active_tab }}'"></contact-gift>
</div>
