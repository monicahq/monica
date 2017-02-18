<div class="col-xs-12 section-title">
  <img src="/img/people/tasks.svg" class="icon-section icon-tasks">
  <h3>{{ trans('people.section_personal_tasks') }}</h3>
</div>


@if ($contact->getTasksInProgress()->count() == 0 and $contact->getCompletedTasks()->count() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.tasks_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="/people/{{ $contact->id }}/tasks/add">{{ trans('people.tasks_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 col-sm-3">
    <div class="sidebar-box">
      {{ trans('people.tasks_desc', ['name' => $contact->getFirstName()]) }}
    </div>
  </div>

  <div class="col-xs-12 col-sm-7 tasks-list">

    @foreach($contact->getTasksInProgress() as $task)

      <div class="task-item">
        <span class="task-title">{{ $task->getTitle() }}</span>

        <span class="task-info">{{ trans('people.tasks_added_on', ['date' => \App\Helpers\DateHelper::getShortDate($task->getCreatedAt(), Auth::user()->locale)]) }}</span>

        <ul class="task-actions horizontal">
          <li><a href="/people/{{ $contact->id }}/tasks/{{ $task->id }}/toggle">{{ trans('people.tasks_mark_complete') }}</a></li>
          <li><a href="/people/{{ $contact->id }}/tasks/{{ $task->id }}/delete" onclick="return confirm('{{ trans('people.tasks_delete_confirmation') }}')">{{ trans('people.tasks_delete') }}</a></li>
        </ul>

        @if (!is_null($task->getDescription()))
        <div class="task-comment">
          {{ $task->getDescription() }}
        </div>
        @endif
      </div>

    @endforeach

    @if ($contact->getCompletedTasks()->count() != 0)

      <div class="tasks-completed">

        @foreach($contact->getCompletedTasks() as $task)

          <div class="task-item">
            <span class="task-title">{{ $task->getTitle() }}</span>

            <span class="task-info">{{ trans('people.tasks_added_on', ['date' => \App\Helpers\DateHelper::getShortDate($task->getCreatedAt(), Auth::user()->locale)]) }}</span>

            <ul class="task-actions horizontal">
              <li><a href="/people/{{ $contact->id }}/tasks/{{ $task->id }}/toggle">{{ trans('people.tasks_reactivate') }}</a></li>
              <li><a href="/people/{{ $contact->id }}/tasks/{{ $task->id }}/delete" onclick="return confirm('{{ trans('people.tasks_delete_confirmation') }}')">{{ trans('people.tasks_delete') }}</a></li>
            </ul>
          </div>

        @endforeach
      </div>
    @endif

  </div>

  {{-- Sidebar --}}
  <div class="col-xs-12 col-sm-2 sidebar">

    <!-- Add activity  -->
    <div class="sidebar-cta">
      <a href="/people/{{ $contact->id }}/tasks/add" class="btn btn-primary">{{ trans('people.tasks_add_task') }}</a>
    </div>

  </div>

@endif
