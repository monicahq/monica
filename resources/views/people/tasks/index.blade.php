<div class="col-xs-12 section-title">
  <img src="/img/people/tasks.svg" class="icon-section icon-tasks">
  <h3>
    {{ trans('people.section_personal_tasks') }}

    <span>
      <a href="/people/{{ $contact->id }}/tasks/add" class="btn">{{ trans('people.tasks_add_task') }}</a>
    </span>
  </h3>
</div>


@if ($contact->getTasksInProgress()->count() == 0 and $contact->getCompletedTasks()->count() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.tasks_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="/people/{{ $contact->id }}/tasks/add">{{ trans('people.tasks_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 tasks-list">

    <p>{{ trans('people.tasks_desc', ['name' => $contact->getFirstName()]) }}</p>

    <ul class="table">
      @foreach($contact->getTasks() as $task)
      <li class="table-row">
        <div class="table-cell activity-date">
          {{ \App\Helpers\DateHelper::getShortDate($task->getCreatedAt(), Auth::user()->locale) }}
        </div>
        <div class="table-cell">
          {{ $task->getTitle() }}
        </div>
        <div class="table-cell activity-actions">
          <a href="/people/{{ $contact->id }}/tasks/{{ $task->id }}/delete" onclick="return confirm('{{ trans('people.tasks_delete_confirmation') }}')">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
          </a>
        </div>
      </li>
      @endforeach
    </ul>

  </div>

@endif
