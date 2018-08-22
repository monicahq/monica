<div class="col-xs-12 section-title {{ direction() }}">
  <contact-gift hash="{{ $contact->hashID() }}" v-bind:gifts-active-tab="'{!! auth()->user()->gifts_active_tab !!}'"></contact-gift>
</div>
