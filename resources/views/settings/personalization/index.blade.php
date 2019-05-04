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
              {{ trans('app.breadcrumb_settings_personalization') }}
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

        <div class="mb3">
          <h3 class="f3 fw5">{{ trans('settings.personalization_tab_title') }}</h3>
          <p>{{ trans('settings.personalization_title') }}</p>
        </div>

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <genders></genders>
          </div>
        </div>

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <reminder-rules></reminder-rules>
          </div>
        </div>

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <contact-field-types></contact-field-types>
          </div>
        </div>

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <activity-types :limited="{{ \Safe\json_encode(auth()->user()->account->hasLimitations()) }}"></activity-types>
          </div>
        </div>

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <modules :limited="{{ \Safe\json_encode(auth()->user()->account->hasLimitations()) }}"></modules>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
