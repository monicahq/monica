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

          @if (! auth()->user()->account->hasLimitations())
            <p class="import">{!! trans('people.people_add_import') !!}</p>
          @endif
        </div>

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <genders></genders>
          </div>
        </div>

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <contact-field-types></contact-field-types>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
