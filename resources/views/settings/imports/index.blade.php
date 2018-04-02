@extends('layouts.skeleton')

@section('content')

<div class="settings import">

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

        <h3 class="with-actions">
          {{ trans('settings.import_title') }}
          <a href="/settings/import/upload" class="btn">{{ trans('settings.import_cta') }}</a>
        </h3>

        <p>{{ trans('settings.import_stat', ['number' => auth()->user()->account->importjobs->count()]) }}</p>

        <ul class="table">
          @foreach (auth()->user()->account->importjobs as $importJob)
          <li class="table-row">
            <div class="table-cell">
              @if (! is_null($importJob->ended_at))
                @if ($importJob->contacts_found != $importJob->contacts_imported)
                  <i class="fa fa-check-circle warning"></i>
                @else
                  <i class="fa fa-check-circle success"></i>
                @endif
              @else
                <i class="fa fa-refresh"></i>
              @endif
              <span class="date">{{ \App\Helpers\DateHelper::getShortDateWithTime($importJob->created_at) }}</span>
            </div>
            <div class="table-cell">
              @if (! is_null($importJob->ended_at))
              {{ trans_choice('settings.import_result_stat', $importJob->contacts_found, ['total_contacts' => $importJob->contacts_found, 'total_imported' => $importJob->contacts_imported, 'total_skipped' => $importJob->contacts_skipped]) }}
              @endif
            </div>
            <div class="table-cell">
              @if (! is_null($importJob->ended_at))
              <a href="/settings/import/report/{{ $importJob->id }}">{{ trans('settings.import_view_report') }}</a>
              @else
              {{ trans('settings.import_in_progress') }}
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
