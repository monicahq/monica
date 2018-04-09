{{-- Pets --}}
<pet hash={!! $contact->hashID() !!}></pet>

{{-- Contact information --}}
<contact-information hash={!! $contact->hashID() !!} ></contact-information>

{{-- Address --}}
<contact-address hash={!! $contact->hashID() !!}></contact-address>

{{-- Introductions --}}
@include('people.dashboard.introductions.index')

{{-- Work --}}
@include('people.dashboard.work.index')

{{-- Food preferences --}}
@include('people.dashboard.food-preferencies.index')
