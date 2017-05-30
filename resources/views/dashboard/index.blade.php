@extends('layouts.skeleton')

@section('content')
  <div class="dashboard-view">

    <!-- Page content -->
    <div class="main-content">

      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12 col-sm-9">

            <!-- {{-- Stats --}}
            {{ $number_of_contacts }}
            {{ $number_of_kids }}
            {{ $number_of_reminders }}
            {{ $number_of_notes }}
            {{ $number_of_activities }}
            {{ $number_of_gifts }}
            {{ $number_of_tasks }}

            % contacts with significant other
            % contacts with kids -->

            {{-- Upcoming reminders --}}
            <h3>{{ trans('dashboard.reminders_title') }}</h3>

            @if ($upcomingReminders->count() != 0)
            <ul>
              @foreach ($upcomingReminders as $reminder)
                <li>
                  <a href="/people/{{ $reminder->contact_id }}">{{ App\Contact::find($reminder->contact_id)->getCompleteName() }}</a>:
                  {{ $reminder->getTitle() }}
                  -
                  {{ $reminder->getNextExpectedDate() }}
                </li>
              @endforeach
            </ul>

            @else

            <p>{{ trans('dashboard.reminders_blank_description') }}</p>

            @endif

            {{-- Events list --}}
            <h3>{{ trans('dashboard.event_title') }}</h3>
            <ul class="event-list">
              @foreach($events as $event)
                <li class="event-list-item">

                  {{-- ICON--}}
                  <div class="event-icon">
                    @if ($event['nature_of_operation'] == 'create')
                      <i class="fa fa-plus-square-o"></i>
                    @endif

                    @if ($event['nature_of_operation'] == 'update')
                      <i class="fa fa-pencil-square-o"></i>
                    @endif
                  </div>

                  {{-- DESCRIPTION --}}
                  <div class="event-description">

                    {{-- YOU ADDED/YOU UPDATED --}}
                    @if ($event['nature_of_operation'] == 'create')
                      {{ trans('dashboard.event_create') }}
                    @endif

                    @if ($event['nature_of_operation'] == 'update')
                      {{ trans('dashboard.event_update') }}
                    @endif

                    {{-- PEOPLE --}}
                    @if ($event['object_type'] == 'contact')
                      @include('dashboard.events._people')
                    @endif

                    {{-- REMINDERS --}}
                    @if ($event['object_type'] == 'reminder')
                      @include('dashboard.events._reminders')
                    @endif

                    {{-- SIGNIFICANT OTHER --}}
                    @if ($event['object_type'] == 'significantother')
                      @include('dashboard.events._significantothers')
                    @endif

                    {{-- KIDS --}}
                    @if ($event['object_type'] == 'kid')
                      @include('dashboard.events._kids')
                    @endif

                    {{-- NOTES --}}
                    @if ($event['object_type'] == 'note')
                      @include('dashboard.events._notes')
                    @endif

                    {{-- ACTIVITIES --}}
                    @if ($event['object_type'] == 'activity')
                      @include('dashboard.events._activities')
                    @endif

                    {{-- TASKS --}}
                    @if ($event['object_type'] == 'task')
                      @include('dashboard.events._tasks')
                    @endif

                    {{-- GIFTS --}}
                    @if ($event['object_type'] == 'gift')
                      @include('dashboard.events._gifts')
                    @endif

                    {{-- DEBTS --}}
                    @if ($event['object_type'] == 'debt')
                      @include('dashboard.events._debts')
                    @endif

                  </div>

                  {{-- DATE --}}
                  <div class="event-date">
                    {{ $event['date'] }}
                  </div>
                </li>
              @endforeach
            </ul>

          </div>

          {{-- Sidebar --}}
          <div class="col-xs-12 col-sm-3 sidebar">

            <!-- Add activity  -->
            <div class="sidebar-cta hidden-xs-down">
              <a href="/people/add" class="btn btn-primary">{{ trans('app.main_nav_cta') }}</a>
            </div>

            <div class="sidebar-box last-seen">
              <h3>Last edited contacts</h3>
              @foreach ($lastUpdatedContacts as $contact)

                @if (count($contact->getInitials()) == 1)
                <div class="avatar one-letter hint--bottom" aria-label="{{ $contact->getCompleteName() }}" style="background-color: {{ $contact->getAvatarColor() }};">
                  {{ $contact->getInitials() }}
                </div>
                @else
                <div class="avatar hint--bottom" aria-label="{{ $contact->getCompleteName() }}" style="background-color: {{ $contact->getAvatarColor() }};">
                  {{ $contact->getInitials() }}
                </div>
                @endif

              @endforeach

              <p><a href="/people">See all other contacts</a></p>
            </div>

          </div>
        </div>
      </div>

    </div>

  </div>
@endsection
