<div class="col-xs-12 section-title">
  <img src="/img/people/activities.svg" class="icon-section icon-activities">
  <h3>{{ trans('people.section_personal_activities') }}</h3>
</div>

@if ($contact->getNumberOfActivities() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.activities_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="/people/{{ $contact->id }}/activities/add">{{ trans('people.activities_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 col-sm-3">
    <div class="sidebar-box">
      {{ trans('people.activity_description', ['name' => $contact->getFirstName()]) }}
    </div>
  </div>

  <div class="col-xs-12 col-sm-7 activities-list">

    {{-- List of activiites --}}
    @foreach($contact->getActivities() as $activity)
      <div class="activity-item">
        <div class="activity-item-info">

          {{ \App\Helpers\DateHelper::getShortDate($activity->getDateItHappened(), Auth::user()->locale) }}

          <div class="activity-item-info-type">
            {{ ucfirst(trans('people.activity_type_'.$activity->getTitle())) }}
          </div>

          <ul class="horizontal activity-item-info-actions">

            @if (! is_null($activity->getDescription()))
            <li>
              <a href="#" class="toggle-description action-link" @click.prevent="activities_description_show = !activities_description_show">
                <span v-if="!activities_description_show">
                  {{ trans('people.activities_more_details') }}
                </span>
                <span v-if="activities_description_show">
                  {{ trans('people.activities_hide_details') }}
                </span>
              </a>
            </li>
            @endif

            <li>
              <a href="/people/{{ $contact->id }}/activities/{{ $activity->id }}/edit" class="action-link">{{ trans('app.edit') }}</a>
            </li>
            <li>
              <a href="/people/{{ $contact->id }}/activities/{{ $activity->id }}/delete" onclick="return confirm('{{ trans('people.activities_delete_confirmation') }}')" class="action-link">{{ trans('app.delete') }}</a>
            </li>
          </ul>

          @if (! is_null($activity->getDescription()))

          <div class="activity-item-info-description" v-if="activities_description_show">
            {{ $activity->getDescription() }}
          </div>

          @endif

        </div>
      </div>
    @endforeach
  </div>

  <!-- Sidebar -->
  <div class="col-xs-12 col-sm-2 sidebar">

    <!-- Add activity  -->
    <div class="sidebar-cta">
      <a href="/people/{{ $contact->id }}/activities/add" class="btn btn-primary">{{ trans('people.activities_add_activity') }}</a>
    </div>

    <!-- Statistics box -->
    <div class="sidebar-box">
      <div class="activities-stats">
        @if (count($contact->getNumberOfActivities()) != 0)
        <p class="sidebar-heading">{{ trans('people.activities_statistics_sidebar_header') }}</p>
        <ul>
          @foreach ($contact->getActivitiesStats() as $stat)
            <li>
              {{ $stat->year }}
              <span>{{ trans_choice('people.activities_statistics_sidebar', $stat->count, ['count' => $stat->count]) }}</span>
            </li>
          @endforeach
        </ul>
        @endif
      </div>
    </div>
  </div>

@endif
