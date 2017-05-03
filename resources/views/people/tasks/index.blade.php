<div class="col-xs-12 section-title">
  <img src="/img/people/tasks.svg" class="icon-section icon-tasks">
  <h3>
    {{ trans('people.section_personal_tasks') }}

    <span>
      <a href="/people/{{ $contact->id }}/tasks/add">{{ trans('people.tasks_add_task') }}</a>
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

    <table class="table table-sm table-hover">
      <thead>
        <tr>
          <th>Date added</th>
          <th>Description</th>
          <th class="actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($contact->getTasks() as $task)
          <tr>
            <td class="date task-{{ $task->status }}">
              {{ \App\Helpers\DateHelper::getShortDate($task->getCreatedAt(), Auth::user()->locale) }}
            </td>
            <td class="task-{{ $task->status }}">
              {{ $task->getTitle() }}

              @if($task->getDescription())
                - {{ $task->getDescription() }}
              @endif
            </td>
            <td class="actions">
              <ul class="task-actions horizontal">
                <li>
                  @if($task->status == 'inprogress')
                  <a href="/people/{{ $contact->id }}/tasks/{{ $task->id }}/toggle">{{ trans('people.tasks_mark_complete') }}</a>
                  @else
                  <a href="/people/{{ $contact->id }}/tasks/{{ $task->id }}/toggle">{{ trans('people.tasks_reactivate') }}</a>
                  @endif
                </li>
                <li><a href="/people/{{ $contact->id }}/tasks/{{ $task->id }}/delete" onclick="return confirm('{{ trans('people.tasks_delete_confirmation') }}')">{{ trans('people.tasks_delete') }}</a></li>
              </ul>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

  </div>

@endif
