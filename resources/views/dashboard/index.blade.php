@extends('layouts.skeleton')

@section('content')
  <div class="dashboard">

    <section class="ph3 ph5-ns pv4 cf w-100 bg-gray-monica">
      <div class="mw9 center">
        <div class="fl w-70 ph2">
          <div class="flex items-center">
            <div class="pr2">
              {{ trans('people.people_list_last_updated') }}
            </div>
            @foreach($lastUpdatedContacts as $contact)
            <div class="pr2 pointer">
              <avatar v-bind:contact="{{ $contact }}" v-bind:clickable="true"></avatar>
            </div>
            @endforeach
          </div>
        </div>
        <div class="fl w-30 tr ph2">
          <a href="/people/add" class="btn btn-primary" style="padding: 15px 45px;">
            {{ trans('people.people_list_blank_cta') }}
          </a>
        </div>
      </div>
    </section>

    {{-- Main section --}}
    <section class="ph3 ph5-ns cf w-100 bg-gray-monica">
      <div class="mw9 center">
        <div class="fl w-50-ns w-100 pa2">
          <div class="br3 ba b--gray-monica bg-white mb4">
            <div class="pa3 bb b--gray-monica">
              <p class="mb0">
                <img src="/img/people/reminders.svg" width="17">
                {{ trans('dashboard.reminders_next_months') }}
              </p>
            </div>
            <div class="pt3 pr3 pl3 mb4">
              {{-- Current month --}}
              @include('dashboard._monthReminder', ['month' => 0])

              {{-- Current month + 1 --}}
              @include('dashboard._monthReminder', ['month' => 1])

              {{-- Current month + 2 --}}
              @include('dashboard._monthReminder', ['month' => 2])
            </div>
          </div>
        </div>
        <div class="fl w-50-ns w-100 pa2">
          <div class="br3 ba b--gray-monica bg-white mb3">
            <div class="pa3 bb b--gray-monica tc">
              <ul>
                <li class="tc dib mr5">
                  <span class="db f3 fw5 green">{{ $number_of_contacts }}</span>
                  <span class="stat-description">{{ trans('dashboard.statistics_contacts') }}</span>
                </li>
                <li class="tc dib mr5">
                  <span class="db f3 fw5 blue">{{ $number_of_activities }}</span>
                  <span class="stat-description">{{ trans('dashboard.statistics_activities') }}</span>
                </li>
                <li class="tc dib">
                  <span class="db f3 fw5 orange">{{ $number_of_gifts }}</span>
                  <span class="stat-description">{{ trans('dashboard.statistics_gifts') }}</span>
                </li>
              </ul>
            </div>
          </div>

          <dashboard-log v-bind:default-active-tab="'{!! auth()->user()->dashboard_active_tab !!}'"></dashboard-log>
        </div>
      </div>
    </section>

  </div>
@endsection
