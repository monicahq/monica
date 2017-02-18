@extends('layouts.skeleton')

@section('content')
  <div class="people-show">

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
    <div class="main-content reminders modal">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-6 col-sm-offset-3">
            <form method="POST" action="/people/{{ $contact->id }}/reminders/store">
              {{ csrf_field() }}

              <h2>{{ trans('people.reminders_add_title', ['name' => $contact->getFirstName()]) }}</h2>

              @include('partials.errors')

              <p>{{ trans('people.reminders_add_description') }}</p>

              {{-- Nature of reminder --}}
              <fieldset class="form-group nature">

                {{-- Pre defined reminder --}}
                <div class="form-check">
                  <label class="form-check-label">
                    <input type="radio" class="form-check-input" name="reminderIsPredefined" value="true"
                              v-model="reminders_predefined"
                              v-bind:value="true"
                              :checked="true">

                    {{ trans('people.reminders_add_predefined') }}
                  </label>

                  <div class="form-group">
                    <select class="form-control" name="reminderPredefinedTypeId">
                      @foreach (\App\ReminderType::all() as $reminderType)
                        @if ($reminderType->id != 1 and $reminderType->id != 6)
                        <option value="{{ $reminderType->id }}">
                            {{ trans($reminderType->translation_key) }} {{ $contact->getFirstName() }}
                        </option>
                        @endif
                      @endforeach
                    </select>
                  </div>
                </div>

                {{-- Custom reminder --}}
                <div class="form-check">
                  <label class="form-check-label">
                      <input type="radio" class="form-check-input" name="reminderIsPredefined" value="false"
                              v-model="reminders_predefined"
                              v-bind:value="false"
                              :checked="false">

                      {{ trans('people.reminders_add_custom') }}
                  </label>

                  <div class="form-group">
                    <input type="text" class="form-control" name="reminderCustomText"
                          :disabled="reminders_predefined == true">
                  </div>
                </div>
              </fieldset>

              {{-- Date --}}
              <div class="form-group">
                <label for="specific_date">{{ trans('people.reminders_add_next_time') }}</label>
                <input type="date" id="reminderNextExpectedDate" name="reminderNextExpectedDate" class="form-control"
                    value="{{ \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}"
                    min="{{ \Carbon\Carbon::now(Auth::user()->timezone)->format('Y-m-d') }}"
                    max="{{ \Carbon\Carbon::now(Auth::user()->timezone)->addYears(10)->format('Y-m-d') }}">

                <fieldset class="form-group frequency">

                  {{-- One time reminder --}}
                  <div class="form-check">
                    <label class="form-check-label">
                      <input type="radio" class="form-check-input" name="frequencyType" value="once"
                                v-model="reminders_frequency"
                                v-bind:value="'once'"
                                :checked="'once'">
                      {{ trans('people.reminders_add_once') }}
                    </label>
                  </div>

                  {{-- Recurring reminder --}}
                  <div class="form-check">
                    <label class="form-check-label">
                        <input type="radio" class="form-check-input" name="frequencyType" value="recurring"
                                v-model="reminders_frequency"
                                v-bind:value="'recurrent'"
                                :checked="'recurrent'">

                        {{ trans('people.reminders_add_recurrent') }}

                        <input type="number" class="form-control frequency-type" name="frequencyRecurringNumber"
                            value="1"
                            min="1"
                            max="99"
                            :disabled="reminders_frequency == 'once'">

                        <select name="reminderRecurringFrequency">
                          <option value="week">{{ trans('people.reminders_type_week') }}</option>
                          <option value="month">{{ trans('people.reminders_type_month') }}</option>
                          <option value="year">{{ trans('people.reminders_type_year') }}</option>
                        </select>

                        {{ trans('people.reminders_add_starting_from') }}

                    </label>
                  </div>
                </fieldset>
              </div>

              <div class="form-group">
                <label for="comment">{{ trans('people.activities_add_optional_comment') }}</label>
                <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
              </div>

              <div class="form-group actions">
                <button type="submit" class="btn btn-primary">{{ trans('people.reminders_add_cta') }}</button>
                <a href="/people/{{ $contact->id }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
