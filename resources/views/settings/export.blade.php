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
              {{ trans('app.breadcrumb_settings_export') }}
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

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <h3>{{ trans('settings.export_title') }}</h3>
            <h4>{{ trans('settings.export_title_sql') }}</h4>
            <p>{{ trans('settings.export_sql_explanation') }}</p>
            <p>{{ trans('settings.export_be_patient') }}</p>
            <p><a href="{{ route('settings.sql') }}" class="btn">{{ trans('settings.export_sql_cta') }}</a></p>
            <p>{!! trans('settings.export_sql_link_instructions', ['url' => 'https://github.com/monicahq/monica/blob/master/docs/installation/update.md#importing-sql-from-the-exporter-feature']) !!}</p>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
