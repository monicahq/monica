<div class="col-xs-12 section-title">
  <img src="/img/people/dashboard.svg" class="icon-section icon-dashboard">
  <h3>{{ trans('people.section_personal_information') }}</h3>
</div>

<div class="col-xs-12 col-sm-3">

  {{-- Section address, email, phone, contact --}}
  @include('people.dashboard.people-information.index')

</div>

<div class="col-xs-12 col-sm-9">

  {{-- Significant Other --}}
  @include('people.dashboard.significantother.index')

  {{-- Kids --}}
  @include('people.dashboard.kids.index')

  {{-- Food preferences --}}
  @include('people.dashboard.food-preferencies.index')

  {{-- Notes --}}
  @include('people.dashboard.notes.index')

</div>
