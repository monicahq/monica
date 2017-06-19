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
              {{ trans('app.breadcrumb_settings_subscriptions') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-sm-9 users-blank">

        <img src="/img/settings/users/blank.svg">

        <h2>{{ trans('settings.users_blank_title') }}</h2>

        <h3>{{ trans('settings.users_blank_add_title') }}</h3>

        <p>{{ trans('settings.users_blank_description') }}</p>

        <p class="cta"><a href="/settings/users/add" class="btn">{{ trans('settings.users_blank_cta') }}</a></p>

      </div>
    </div>
  </div>
</div>

@endsection
