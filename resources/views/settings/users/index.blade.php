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

      <div class="col-xs-12 col-sm-9 users-list">

        <h3 class="with-actions">
          {{ trans('settings.users_list_title') }}
          <a href="/settings/users/add" class="btn">{{ trans('settings.users_list_add_user') }}</a>
        </h3>
        <ul class="table">
        @foreach ($users as $user)
          <li class="table-row">
            <div class="table-cell">
              {{ $user->name }} ({{ $user->email }})
            </div>
            <div class="table-cell actions">
              @if ($user->id == auth()->user()->id)
                {{ trans('settings.users_list_you') }}
              @else
                <a href="/settings/users/{{ $user->id }}/delete" onclick="return confirm('{{ trans('settings.users_list_delete_confirmation') }}')">
                  <i class="fa fa-trash-o" aria-hidden="true"></i>
                </a>
              @endif
            </div>
          </li>
        @endforeach
        </ul>

        @if (auth()->user()->account->invitations()->count() != 0)
          <h3>{{ trans('settings.users_list_invitations_title') }}</h3>

          <p>{{ trans('settings.users_list_invitations_explanation') }}</p>

          <ul class="table">
          @foreach (auth()->user()->account->invitations as $invitation)
              <li class="table-row">
                <div class="table-cell">
                  {{ $invitation->email }}
                </div>
                <div class="table-cell">
                  {{ trans('settings.users_list_invitations_invited_by', ['name' => $invitation->invitedBy->name]) }}
                </div>
                <div class="table-cell">
                  {{ trans('settings.users_list_invitations_sent_date', ['date' => \App\Helpers\DateHelper::getShortDate($invitation->created_at)]) }}
                </div>
                <div class="table-cell actions">
                  <a href="/settings/users/invitations/{{ $invitation->id }}/delete" onclick="return confirm('{{ trans('settings.users_invitations_delete_confirmation') }}')">
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
