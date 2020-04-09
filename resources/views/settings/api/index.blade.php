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
              {{ trans('app.breadcrumb_api') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-12 col-sm-9">

        <div class="mb3">
          <h2 class="f3 fw5">{{ trans('settings.api_title') }}</h2  >
          <p>{{ trans('settings.api_description') }}</p>
          <p>{!! trans('settings.api_help', ['url' => config('api.help')]) !!}</p>
          <p>
            {{ trans('settings.api_endpoint') }}
            <input value="{{ route('api') }}" class="url form-control" type="text" readonly />
          </p>
        </div>

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <passport-personal-access-tokens></passport-personal-access-tokens>
          </div>
        </div>

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <passport-clients></passport-clients>
            <passport-authorized-clients class="mt4"></passport-authorized-clients>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
