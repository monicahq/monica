@extends('layouts.skeleton')

@section('content')

<div class="settings">

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ auth()->user()->getFluidLayout() }}">
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
              {{ trans('app.breadcrumb_api') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ auth()->user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-md-9">

        <h3 class="with-actions">{{ trans('settings.api_title') }}</h3>
        <p>{{ trans('settings.api_description') }}</p>

        <h3>{{ trans('settings.api_personal_access_tokens') }}</h3>
        <p>{{ trans('settings.api_pao_description') }}</p>
        <passport-personal-access-tokens></passport-personal-access-tokens>

        <h3>{{ trans('settings.api_oauth_clients') }}</h3>
        <p>{{ trans('settings.api_oauth_clients_desc') }}</p>
        <passport-clients></passport-clients>

        <h3>{{ trans('settings.api_authorized_clients') }}</h3>
        <p>{{ trans('settings.api_authorized_clients_desc') }}</p>
        <passport-authorized-clients></passport-authorized-clients>

      </div>
    </div>
  </div>
</div>

@endsection
