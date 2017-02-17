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
    <div class="main-content reminders">

      @if ($contact->getNumberOfReminders() == 0)

        <div class="reminders-list blank-people-state">
          <div class="{{ Auth::user()->getFluidLayout() }}">
            <div class="row">
              <div class="col-xs-12">
                <h3>{{ trans('people.reminders_blank_title', ['name' => $contact->getFirstName()]) }}</h3>
                <div class="cta-blank">
                  <a href="/people/{{ $contact->id }}/reminders/add" class="btn btn-primary">{{ trans('people.reminders_blank_add_activity') }}</a>
                </div>
                <div class="illustration-blank">
                  <img src="/img/people/reminders/clock.svg">
                  <p>{{ trans('people.reminders_blank_description', ['name' => $contact->getFirstName()]) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

      @else

        <div class="{{ Auth::user()->getFluidLayout() }}">
          <div class="row">
            <div class="col-xs-12 col-sm-9 reminders-list">

              @foreach($contact->getReminders() as $reminder)

                @include('people.reminders._reminder_item')

              @endforeach
            </div>

            {{-- Sidebar --}}
            <div class="col-xs-12 col-sm-3 sidebar">

              <!-- Add activity  -->
              <div class="sidebar-cta hidden-xs-down">
                <a href="/people/{{ $contact->id }}/reminders/add" class="btn btn-primary">{{ trans('people.reminders_cta') }}</a>
              </div>

              <p>{{ trans('people.reminders_description') }}</p>

            </div>
          </div>
        </div>

      @endif

    </div>

  </div>
@endsection
