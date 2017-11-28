<div class="col-xs-12 section-title">
  <img src="/img/people/tasks.svg" class="icon-section icon-tasks">
  <h3>
    {{ trans('people.section_personal_tasks') }}

    <span>
      <a href="/people/{{ $contact->id }}/tasks/add" class="btn">{{ trans('people.tasks_add_task') }}</a>
    </span>
  </h3>
</div>


@if ($contact->getTasksInProgress()->count() === 0 and $contact->getCompletedTasks()->count() === 0)

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
      @foreach($contact->tasks as $task)
      <li class="table-row">
        <div class="table-cell date">
          {{ \App\Helpers\DateHelper::getShortDate($task->created_at) }}
        </div>
        <div class="table-cell">
          {{ $task->title }}
        </div>
        <div class="table-cell list-actions">
          <a href="#" onclick="if (confirm('{{ trans('people.tasks_delete_confirmation') }}')) { $(this).closest('.table-row').find('.entry-delete-form').submit(); } return false;">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
          </a>
        </div>

        <form method="POST" action="{{ action('Contacts\\TasksController@destroy', compact('contact', 'task')) }}" class="entry-delete-form hidden">
          {{ method_field('DELETE') }}
          {{ csrf_field() }}
        </form>
      </li>
      @endforeach
    </ul>

  </div>

@endif
