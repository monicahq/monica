{{-- Pets --}}
@if ($modules->contains('key', 'pets'))
<pet hash="{!! $contact->hashID() !!}"></pet>
@endif

{{-- Contact information --}}
@if ($modules->contains('key', 'contact_information'))
<contact-information hash="{!! $contact->hashID() !!}"></contact-information>
@endif

{{-- Address --}}
@if ($modules->contains('key', 'addresses'))
<contact-address hash="{!! $contact->hashID() !!}"></contact-address>
@endif

{{-- Introductions --}}
@if ($modules->contains('key', 'introductions'))
@include('people.dashboard.introductions.index')
@endif

{{-- Work --}}
@if ($modules->contains('key', 'work_information'))
@include('people.dashboard.work.index')
@endif

{{-- Food preferences --}}
@if ($modules->contains('key', 'food_preferences'))
@include('people.dashboard.food-preferencies.index')
@endif
