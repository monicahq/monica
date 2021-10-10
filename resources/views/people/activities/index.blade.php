<div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-100 pb3 pt1 pl3 pr3">
  <div class="br2 bg-white mb2">

    <activity-list hash="{{ $contact->hashID() }}" :contact-id="{{ $contact->id }}" name="{{ $contact->first_name }}" />

  </div>
</div>
