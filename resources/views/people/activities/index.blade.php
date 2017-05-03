<div class="col-xs-12 section-title">
  <img src="/img/people/activities.svg" class="icon-section icon-activities">
  <h3>
    {{ trans('people.section_personal_activities') }}

    <span><a href="/people/{{ $contact->id }}/activities/add">{{ trans('people.activities_add_activity') }}</a></span>
  </h3>
</div>

@if ($contact->getNumberOfActivities() == 0)

  <div class="col-xs-12">
    <div class="section-blank">
      <h3>{{ trans('people.activities_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
      <a href="/people/{{ $contact->id }}/activities/add">{{ trans('people.activities_blank_add_activity') }}</a>
    </div>
  </div>

@else

  <div class="col-xs-12 activities-list">

    <p>{{ trans('people.activity_description', ['name' => $contact->getFirstName()]) }}</p>

    <table class="table table-sm table-hover">
      <thead>
        <tr>
          <th>Date</th>
          <th>Nature</th>
          <th class="actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($contact->getActivities() as $activity)
        <tr>
          <td class="date">{{ \App\Helpers\DateHelper::getShortDate($activity->getDateItHappened(), Auth::user()->locale) }}</td>
          <td>{{ ucfirst(trans('people.activity_type_'.$activity->getTitle())) }}</td>
          <td class="actions">
            <ul class="horizontal">

              @if (! is_null($activity->getDescription()))
              <li>
                <a href="#" class="toggle-description" @click.prevent="activities_description_show = !activities_description_show">
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
                <a href="/people/{{ $contact->id }}/activities/{{ $activity->id }}/edit">{{ trans('app.edit') }}</a>
              </li>
              <li>
                <a href="/people/{{ $contact->id }}/activities/{{ $activity->id }}/delete" onclick="return confirm('{{ trans('people.activities_delete_confirmation') }}')">{{ trans('app.delete') }}</a>
              </li>
            </ul>

            @if (! is_null($activity->getDescription()))

            <div class="activity-item-info-description" v-if="activities_description_show">
              {{ $activity->getDescription() }}
            </div>

            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

@endif
