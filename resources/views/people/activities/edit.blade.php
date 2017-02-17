@extends('layouts.skeleton')

@section('content')
  <div class="people-show activities">

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
    <div class="main-content modal">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">
            <form method="POST" action="/people/{{ $contact->id }}/activities/{{ $activity->id }}/save">
              {{ csrf_field() }}

              <h2>{{ trans('people.activities_add_title', ['name' => $contact->getFirstName()]) }}</h2>

              {{-- Build the Activity types dropdown --}}
              <div class="form-group">
                <label for="activityType">{{ trans('people.activities_add_pick_activity') }}</label>
                <select id="activityType" name="activityType" class="form-control" required>
                  @foreach (App\ActivityTypeGroup::all() as $activityTypeGroup)
                    <optgroup label="{{ trans('people.activity_type_group_'.$activityTypeGroup->key) }}">
                      @foreach (App\ActivityType::where('activity_type_group_id', $activityTypeGroup->id)->get() as $activityType)
                        @if ($activity->getTitle() == $activityType->key)
                        <option value="{{ $activityType->id }}" selected>
                          {{ trans('people.activity_type_'.$activityType->key) }}
                        </option>
                        @else
                        <option value="{{ $activityType->id }}">
                          {{ trans('people.activity_type_'.$activityType->key) }}
                        </option>
                        @endif
                      @endforeach
                    </optgroup>
                  @endforeach
                </select>
              </div>

              <div class="form-group">
                <label for="specific_date">{{ trans('people.activities_add_date_occured') }}</label>
                <input type="date" id="specific_date" name="specific_date" class="form-control"
                    value="{{ $activity->date_it_happened->format('Y-m-d') }}"
                    min="{{ \Carbon\Carbon::now(Auth::user()->timezone)->subYears(10)->format('Y-m-d') }}"
                    max="{{ \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}">
              </div>

              <div class="form-group">
                <label for="comment">{{ trans('people.activities_add_optional_comment') }}</label>
                <textarea class="form-control" id="comment" name="comment" rows="3">{{ $activity->getDescription() }}</textarea>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('people.activities_add_cta') }}</button>
                <a href="/people/{{ $contact->id }}/activities" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div> <!-- .form-group -->
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
