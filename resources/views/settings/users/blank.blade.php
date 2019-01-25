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
              <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li>
              <a href="{{ route('settings.index') }}">{{ trans('app.breadcrumb_settings') }}</a>
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

      <div class="col-xs-12 col-sm-9 blank-screen">

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            <img src="img/settings/users/blank.svg">

            <h2>{{ trans('settings.users_blank_title') }}</h2>

            <h3>{{ trans('settings.users_blank_add_title') }}</h3>

            <p>{{ trans('settings.users_blank_description') }}</p>

            <p class="cta"><a href="{{ route('settings.users.create') }}" class="btn">{{ trans('settings.users_blank_cta') }}</a></p>

            @if (config('monica.requires_subscription') && auth()->user()->account->hasLimitations())
              <p class="requires-subscription">{{ trans('settings.users_invitation_need_subscription') }}</p>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
