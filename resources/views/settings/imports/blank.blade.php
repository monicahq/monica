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
              {{ trans('app.breadcrumb_settings_import') }}
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

        <img src="/img/settings/imports/import.svg">

        <h2>You haven't imported any contacts yet.</h2>

        <h3>Would you like to import contacts now?</h3>

        <p>We can import vCard files that you can get from Google Contacts or your Contact manager.</p>

        <p class="cta"><a href="/settings/import/upload" class="btn">Import vCard</a></p>

      </div>

    </div>
  </div>
</div>

@endsection
