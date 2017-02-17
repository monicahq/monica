<ul class="nav nav-tabs">

  @if (Route::currentRouteNamed('people.reminders') || Route::currentRouteNamed('people.reminders.add'))
  <li class="nav-item">
    <a class="nav-link active" href="/people/{{ $contact->id }}/reminders">
      <img src="/img/people/reminders.svg" class="icon-reminders">
      {{ trans('people.show_reminders') }}
      <span class="counter">{{ $contact->getNumberOfReminders() }}</span>
    </a>
  </li>
  @else
  <li class="nav-item">
    <a class="nav-link" href="/people/{{ $contact->id }}/reminders">
      <img src="/img/people/reminders.svg" class="icon-reminders">
      {{ trans('people.show_reminders') }}
      <span class="counter">{{ $contact->getNumberOfReminders() }}</span>
    </a>
  </li>
  @endif

  @if (Route::currentRouteNamed('people.tasks') || Route::currentRouteNamed('people.tasks.add'))
  <li class="nav-item">
    <a class="nav-link active" href="/people/{{ $contact->id }}/tasks">
      <img src="/img/people/tasks.svg" class="icon-tasks">
      {{ trans('people.show_tasks') }}
      <span class="counter">{{ $contact->getTasksInProgress()->count() }}</span>
    </a>
  </li>
  @else
  <li class="nav-item">
    <a class="nav-link" href="/people/{{ $contact->id }}/tasks">
      <img src="/img/people/tasks.svg" class="icon-tasks">
      {{ trans('people.show_tasks') }}
      <span class="counter">{{ $contact->getTasksInProgress()->count() }}</span>
    </a>
  </li>
  @endif

  @if (Route::currentRouteNamed('people.gifts') || Route::currentRouteNamed('people.gifts.add'))
  <li class="nav-item">
    <a class="nav-link active" href="/people/{{ $contact->id }}/gifts">
      <img src="/img/people/gifts.svg" class="icon-gifts">
      {{ trans('people.show_gifts') }}
      <span class="counter">{{ $contact->getNumberOfGifts() }}</span>
    </a>
  </li>
  @else
  <li class="nav-item">
    <a class="nav-link" href="/people/{{ $contact->id }}/gifts">
      <img src="/img/people/gifts.svg" class="icon-gifts">
      {{ trans('people.show_gifts') }}
      <span class="counter">{{ $contact->getNumberOfGifts() }}</span>
    </a>
  </li>
  @endif

</ul>
