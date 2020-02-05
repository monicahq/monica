@extends('layouts.skeleton')

@section('content')

<div class="settings">

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ Auth::user()->getFluidLayout() }}">
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
              {{ trans('app.breadcrumb_settings_subscriptions') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="main-content central-form subscriptions">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-12 col-sm-6 offset-sm-3 offset-sm-3-right downgrade">
          @include('partials.errors')

          <div class="br3 ba b--gray-monica bg-white mb4">
            <div class="pa3 bb b--gray-monica">
              <h2>{{ trans('settings.archive_title') }}</h2>

              <p class="tc">{{ trans('settings.archive_desc') }}</p>

              <form method="POST" action="{{ route('settings.subscriptions.archive') }}">
                @csrf

                <p class="mb4 tc"><button type="submit" class="btn btn-primary">{{ trans('settings.archive_cta') }}</button></p>

              </form>

            </div>
          </div>
        </div>
      </div>
    </div>

    @endsection
