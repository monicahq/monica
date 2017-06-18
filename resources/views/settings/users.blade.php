@extends('layouts.skeleton')

@section('content')

<div class="settings">

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
              <a href="/settings">{{ trans('app.breadcrumb_settings') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_settings_users') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-sm-9">

        <p><a href="/settings/users/add">Add a new user</a></p>

        <h2>Users</h2>
        <ul class="table">
        @foreach ($users as $user)
          <li class="table-row">
            <div class="table-cell">
              {{ $user->name }} ({{ $user->email }})
            </div>
            <div class="table-cell">
              @if ($user->id == auth()->user()->id)
                <span class="actions">That's you</span>
              @else
                <a href="" onclick="return confirm('{{ trans('people.gifts_delete_confirmation') }}')">
                  <i class="fa fa-trash-o" aria-hidden="true"></i>
                </a>
              @endif
            </div>
          </li>
        @endforeach
        </ul>

        <h2>Pending invitations</h2>

        @if (auth()->user()->account->invitations()->count() != 0)
          <ul class="table">
          @foreach (auth()->user()->account->invitations as $invitation)
              <li class="table-row">
                <div class="table-cell">
                  {{ $invitation->email }}
                </div>
                <div class="table-cell">
                  invited by {{ $invitation->invitedBy->name }}
                </div>
                <div class="table-cell">
                  sent on {{ $invitation->created_at }}
                </div>
                <div class="table-cell">
                  <a href="/settings/users/invitations/{{ $invitation->id }}/delete" onclick="return confirm('{{ trans('people.reminders_delete_confirmation') }}')">
                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                  </a>
                </div>
              </li>
          @endforeach
          </ul>
        @endif

      </div>
    </div>
  </div>
</div>

@endsection
