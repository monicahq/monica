@extends('layouts.skeleton')

@section('content')

<div class="settings">

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ auth()->user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-12">
          <ul class="horizontal">
            <li>
              <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li>
              <a href="{{ route('settings.index') }}">{{ trans('app.breadcrumb_settings') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_dav') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ auth()->user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-12 col-md-9">
        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            <dav-resources
              :dav-route="'{{ $davRoute }}'"
              :card-dav-route="'{{ $cardDavRoute }}'"
              :cal-dav-birthdays-route="'{{ $calDavBirthdaysRoute }}'"
              :cal-dav-tasks-route="'{{ $calDavTasksRoute }}'"
            ></dav-resources>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
