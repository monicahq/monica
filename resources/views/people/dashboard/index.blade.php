{{-- Pets --}}
@if ($modules->contains('key', 'pets'))
<pet v-bind:contact-id="{!! $contact->id !!}"></pet>
@endif

{{-- Contact information --}}
@if ($modules->contains('key', 'contact_information'))
<contact-information v-bind:contact-id="{!! $contact->id !!}"></contact-information>
@endif

{{-- Address --}}
@if ($modules->contains('key', 'addresses'))
<contact-address v-bind:contact-id="{!! $contact->id !!}"></contact-address>
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
