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

      <div class="col-xs-12 col-sm-9">

        <h3>{{ trans('settings.import_title') }}</h3>

        <p>You have {{auth()->user()->account->importjobs->count()}} jobs.</p>

        <ul class="table">
          @foreach (auth()->user()->account->importjobs as $importJob)
          <li class="table-row">
            <div class="table-cell">
              {{ $importJob->created_at }}
              {{ $importJob->failed }}
            </div>
            <div class="table-cell">
              {{ $importJob->type }}:
              {{ $importJob->contacts_found }} contacts found ({{ $importJob->contacts_imported }} contacts imported and {{ $importJob->contacts_skipped }} contacts skipped)
            </div>
            <div class="table-cell">
              @if (! is_null($importJob->ended_at))
              <a href="/settings/import/{{ $importJob->id }}">View report</a>
              @else
              In progress
              @endif
            </div>
          </li>
          @endforeach
        </ul>

      </div>
    </div>
  </div>
</div>

@endsection
