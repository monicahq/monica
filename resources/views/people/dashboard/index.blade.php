{{-- Pets --}}
<pet v-bind:contact-id="{!! $contact->id !!}"></pet>

{{-- Contact information --}}
<contact-information v-bind:contact-id="{!! $contact->id !!}"></contact-information>

{{-- Address --}}
<contact-address v-bind:contact-id="{!! $contact->id !!}"></contact-address>

{{-- Introductions --}}
@include('people.dashboard.introductions.index')

{{-- Work --}}
@include('people.dashboard.work.index')

{{-- Food preferences --}}
@include('people.dashboard.food-preferencies.index')
