<div class="col-xs-12 section-title">
  <img src="/img/people/reminders.svg" class="icon-section icon-reminders">
  <h3>{{ trans('people.section_personal_reminders') }}</h3>
</div>


@if ($contact->getNumberOfReminders() == 0)

  <div class="col-xs-12">
    <h3>{{ trans('people.reminders_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
    <div class="cta-blank">
      <a href="/people/{{ $contact->id }}/reminders/add" class="btn btn-primary">{{ trans('people.reminders_blank_add_activity') }}</a>
    </div>
    <div class="illustration-blank">
      <img src="/img/people/reminders/clock.svg">
      <p>{{ trans('people.reminders_blank_description', ['name' => $contact->getFirstName()]) }}</p>
    </div>
  </div>

@else

  <div class="col-xs-12 col-sm-9 reminders-list">

    @foreach($contact->getReminders() as $reminder)

      @include('people.reminders._reminder_item')

    @endforeach
  </div>

  {{-- Sidebar --}}
  <div class="col-xs-12 col-sm-3 sidebar">

    <!-- Add activity  -->
    <div class="sidebar-cta hidden-xs-down">
      <a href="/people/{{ $contact->id }}/reminders/add" class="btn btn-primary">{{ trans('people.reminders_cta') }}</a>
    </div>

    <p>{{ trans('people.reminders_description') }}</p>

  </div>

@endif
