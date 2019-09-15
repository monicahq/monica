{{-- Pets --}}
@if ($modules->contains('key', 'pets'))
<pet hash="{{ $contact->hashID() }}"></pet>
@endif

{{-- Contact information --}}
@if ($modules->contains('key', 'contact_information'))
<contact-information hash="{{ $contact->hashID() }}"></contact-information>
@endif

{{-- Address --}}
@if ($modules->contains('key', 'addresses'))
<contact-address hash="{{ $contact->hashID() }}"></contact-address>
@endif

{{-- Introductions --}}
@if ($modules->contains('key', 'how_you_met') && ! $contact->isMe())
@include('people.introductions.index')
@endif

{{-- Work --}}
@if ($modules->contains('key', 'work_information'))
@include('people.work.index')
@endif

{{-- Food preferences --}}
@if ($modules->contains('key', 'food_preferences') && ! $contact->isMe())
@include('people.food-preferences.index')
@endif
