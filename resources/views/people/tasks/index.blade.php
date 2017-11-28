<div class="col-xs-12 section-title">
  <img src="/img/people/tasks.svg" class="icon-section icon-tasks">
  <h3>
    {{ trans('people.section_personal_tasks') }}

    <span>
      <a href="/people/{{ $contact->id }}/tasks/add" class="btn">{{ trans('people.tasks_add_task') }}</a>
    </span>
  </h3>

  <contact-task v-bind:contact-id="{!! $contact->id !!}"></contact-task>
</div>

@if ($contact->getTasksInProgress()->count() === 0 and $contact->getCompletedTasks()->count() === 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.tasks_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="/people/{{ $contact->id }}/tasks/add">{{ trans('people.tasks_blank_add_activity') }}</a>
    </div>
  </div>

@endif
