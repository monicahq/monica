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
              {{ trans('app.breadcrumb_settings_security') }}
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

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            <p>{{ trans('settings.u2f_enable_description') }}</p>

            <u2f-connector
              :currentkeys="{{ json_encode($currentKeys) }}"
              :registerdata="{{ json_encode($registerData) }}"
              :method="'register'"
              :callbackurl="{{ json_encode(route('security.index')) }}">
            </u2f-connector>

            <a href="{{ route('security.index') }}" class="btn">{{ trans('app.cancel') }}</a>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="{{ asset(mix('js/u2f-api.js')) }}" type="text/javascript"></script>

@endsection
