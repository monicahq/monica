<div class="col-xs-12 section-title">
  <img src="/img/people/activities.svg" class="icon-section icon-activities">
  <h3>
    {{ trans('people.section_personal_activities') }}

    <span><a href="/people/{{ $contact->id }}/activities/add" class="btn">{{ trans('people.activities_add_activity') }}</a></span>
  </h3>
</div>

@if ($contact->activities->count() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.activities_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="/people/{{ $contact->id }}/activities/add">{{ trans('people.activities_blank_add_activity') }}</a>
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
          <a href="/people/{{ $contact->id }}/activities/{{ $activity->id }}/edit" class="edit">
            <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
          </a>
          <a href="/people/{{ $contact->id }}/activities/{{ $activity->id }}/delete" onclick="return confirm('{{ trans('people.activities_delete_confirmation') }}')">
            <i class="fa fa-trash-o" aria-hidden="true"></i>
          </a>
        </div>
      </li>
      @endforeach
    </ul>
  </div>

@endif
