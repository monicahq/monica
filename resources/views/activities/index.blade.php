<div class="col-xs-12 section-title {{ \App\Helpers\LocaleHelper::getDirection() }}">
  <img src="/img/people/activities.svg" class="icon-section icon-activities">
  <h3>
    {{ trans('people.section_personal_activities') }}

    <span class="{{ \App\Helpers\LocaleHelper::getDirection() == 'ltr' ? 'fr' : 'fl' }}">
      <a href="{{ route('activities.add', $contact) }}" class="btn">{{ trans('people.activities_add_activity') }}</a>
    </span>
  </h3>
</div>

@if ($contact->activities->count() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.activities_blank_title', ['name' => $contact->first_name]) }}</h3>
      <a href="{{ route('activities.add', $contact) }}">{{ trans('people.activities_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 activities-list">

    <ul class="table">
      @foreach($contact->activities as $activity)
      <li class="table-row">
        <div class="table-cell date">
          {{ \App\Helpers\DateHelper::getShortDate($activity->getDateItHappened()) }}
        </div>
        <div class="table-cell">
          {{ $activity->getSummary() }}
        </div>
        <div class="table-cell list-actions">
            <a href="#activity{{$activity->id}}Modal" data-toggle="modal">
              <i class="fa fa-eye" aria-hidden="true"></i>
            </a>
          <a href="{{ route('activities.edit', [$activity, $contact]) }}" class="edit">
            <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
          </a>
          <a href="#" onclick="if (confirm('{{ trans('people.activities_delete_confirmation') }}')) { $(this).closest('.table-row').find('.entry-delete-form').submit(); } return false;">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
          </a>
        </div>

        <form method="POST" action="{{ action('ActivitiesController@destroy', compact('contact', 'activity')) }}" class="entry-delete-form hidden">
          {{ method_field('DELETE') }}
          {{ csrf_field() }}
        </form>
      </li>

      @endforeach
    </ul>
  </div>
  @foreach($contact->activities as $activity)

      @include('people.modal.activity_view')

  @endforeach
@endif
