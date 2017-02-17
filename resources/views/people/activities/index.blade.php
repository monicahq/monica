@extends('layouts.skeleton')

@section('content')
  <div class="people-show">
    {{ csrf_field() }}

    {{-- Breadcrumb --}}
    <div class="breadcrumb">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12">
            <ul class="horizontal">
              <li>
                <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
              </li>
              <li>
                <a href="/people">{{ trans('app.breadcrumb_list_contacts') }}</a>
              </li>
              <li>
                {{ $contact->getCompleteName() }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page header -->
    @include('people._header')

    <!-- Page content -->

    @if ($contact->getNumberOfActivities() == 0)

      <div class="activities-list blank-people-state">
        <div class="{{ Auth::user()->getFluidLayout() }}">
          <div class="row">
            <div class="col-xs-12">
              <h3>{{ trans('people.activities_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
              <div class="cta-blank">
                <a href="/people/{{ $contact->id }}/activities/add" class="btn btn-primary">{{ trans('people.activities_blank_add_activity') }}</a>
              </div>
              <div class="illustration-blank">
                <img src="/img/people/activities/blank.svg">
                <p>{{ trans('people.activities_blank_description', ['name' => $contact->getFirstName()]) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

    @else

      <div class="main-content activities">

        <div class="{{ Auth::user()->getFluidLayout() }}">
          <div class="row">
            <div class="col-xs-12 col-sm-3">
              <p class="sidebar-heading">
                {{ trans('people.activity_title') }}
              </p>

              <p>
                {{ trans('people.activity_description', ['name' => $contact->getFirstName()]) }}
              </p>

            </div>

            <div class="col-xs-12 col-sm-7 activities-list">

              <!-- only on mobile -->
              <div class="cta-mobile hidden-sm-up">
                <a href="/people/{{ $contact->id }}/activities/add" class="btn btn-primary">{{ trans('people.activities_add_activity') }}</a>
              </div>

              <!-- List of activities -->
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
              <div class="sidebar-cta hidden-xs-down">
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
          </div>
        </div>

      </div>

    @endif

  </div>
@endsection
