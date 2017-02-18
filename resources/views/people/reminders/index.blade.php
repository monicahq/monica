<div class="col-xs-12 section-title">
  <img src="/img/people/reminders.svg" class="icon-section icon-reminders">
  <h3>{{ trans('people.section_personal_reminders') }}</h3>
</div>


@if ($contact->getNumberOfReminders() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.reminders_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="/people/{{ $contact->id }}/reminders/add">{{ trans('people.reminders_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 col-sm-3">
    <div class="sidebar-box">
      {{ trans('people.reminders_description') }}
    </div>
  </div>

  <div class="col-xs-12 col-sm-7 reminders-list">

    @foreach($contact->getReminders() as $reminder)

      @include('people.reminders._reminder_item')

    @endforeach
  </div>

  {{-- Sidebar --}}
  <div class="col-xs-12 col-sm-2 sidebar">

    <!-- Add activity  -->
    <div class="sidebar-cta">
      <a href="/people/{{ $contact->id }}/reminders/add" class="btn btn-primary">{{ trans('people.reminders_cta') }}</a>
    </div>

  </div>

@endif
