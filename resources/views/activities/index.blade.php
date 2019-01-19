<div class="col-xs-12 section-title">
  <img src="img/people/activities.svg" class="icon-section icon-activities">
  <h3>
    {{ trans('people.section_personal_activities') }}

    <span class="{{ htmldir() == 'ltr' ? 'fr' : 'fl' }}">
      <a href="{{ route('activities.add', $contact) }}" cy-name="add-activity-button" class="btn">{{ trans('people.activities_add_activity') }}</a>
    </span>
  </h3>
</div>

@if ($contact->activities->count() == 0)

  <div class="col-xs-12" cy-name="activities-blank-state">
    <div class="section-blank">
      <h3>{{ trans('people.activities_blank_title', ['name' => $contact->first_name]) }}</h3>
      <a href="{{ route('activities.add', $contact) }}">{{ trans('people.activities_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 activities-list">

    <ul class="table">
      @foreach($contact->activities as $activity)
      <li class="table-row" cy-name="activity-body-{{ $activity->id }}">
        <div class="table-cell date">
          {{ App\Helpers\DateHelper::getShortDate($activity->date_it_happened) }}
        </div>
        <div class="table-cell">
          {{ $activity->getSummary() }}
        </div>
        <div class="table-cell list-actions">
            <a href="#activity{{$activity->id}}Modal" cy-name="view-activity-button-{{ $activity->id }}" data-toggle="modal">
              <i class="fa fa-eye" aria-hidden="true"></i>
            </a>
          <a href="{{ route('activities.edit', [$activity, $contact]) }}" cy-name="edit-activity-button-{{ $activity->id }}" class="edit">
            <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
          </a>
          <a href="{{ route('people.show', $contact) }}" cy-name="delete-activity-button-{{ $activity->id }}" onclick="if (confirm('{{ trans('people.activities_delete_confirmation') }}')) { $(this).closest('.table-row').find('.entry-delete-form').submit(); } return false;">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
          </a>
        </div>

        <form method="POST" action="{{ route('activities.delete', [$activity, $contact]) }}" class="entry-delete-form hidden">
          {{ method_field('DELETE') }}
          {{ csrf_field() }}
        </form>
      </li>

      @endforeach
    </ul>

    @if ($contact->activities->count() != 0)
    <p class="tc">📗 <a href="{{ route('people.activities.index', $contact) }}">{{ trans('people.activities_view_activities_report') }}</a></p>
    @endif
  </div>
  @foreach($contact->activities as $activity)

      @include('people.modal.activity_view')

  @endforeach
@endif
