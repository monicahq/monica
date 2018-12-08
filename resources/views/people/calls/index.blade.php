<div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-100 pb3 pt1 pl3 pr3">
  <div class="br2 bg-white mb2">

    <phone-call-list hash="{{ $contact->hashID() }}" name="{{ $contact->first_name }}"></phone-call-list>

  </div>
</div>
