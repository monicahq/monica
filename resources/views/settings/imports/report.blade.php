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
                  <a href="{{ route('settings.import') }}">{{ trans('app.breadcrumb_settings_import') }}</a>
                </li>
                <li>
                  {{ trans('app.breadcrumb_settings_import_report') }}
                </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Page content -->
    <div class="main-content central-form report">
      <div class="{{ Auth::user()->getFluidLayout() }}">
        <div class="row">
          <div class="col-xs-12">

            <h2>{{ trans('settings.import_report_title') }}</h2>

            <ul class="report-summary">
              <li>{{ trans('settings.import_report_date') }}: <span>{{ \App\Helpers\DateHelper::getShortDate($importJob->created_at) }}</span></li>
              <li>{{ trans('settings.import_report_type') }}: <span>{{ $importJob->type }}</span></li>
              <li>{{ trans('settings.import_report_number_contacts') }}: <span>{{ $importJob->contacts_found }}</span></li>
              <li>{{ trans('settings.import_report_number_contacts_imported') }}: <span>{{ $importJob->contacts_imported }}</span></li>
              <li>{{ trans('settings.import_report_number_contacts_skipped') }}: <span>{{ $importJob->contacts_skipped }}</span></li>
            </ul>

            <ul class="table">

              @foreach ($importJob->importJobReports as $importJobReport)
              <li class="table-row">
                <div class="table-cell status">
                  @if ($importJobReport->skipped == 0)
                    <span class="badge badge-success">{{ trans('settings.import_report_status_imported') }}</span>
                  @else
                    <span class="badge badge-danger">{{ trans('settings.import_report_status_skipped') }}</span>
                  @endif
                </div>
                <div class="table-cell">
                  {{ $importJobReport->contact_information }}
                </div>
                <div class="table-cell reason">
                  @if (! is_null($importJobReport->skip_reason))
                  {{--
                    settings.import_vcard_contact_exist
                    settings.import_vcard_contact_no_firstname
                    --}}
                  {{ trans('settings.'.$importJobReport->skip_reason) }}
                  @endif
                </div>
              </li>
              @endforeach

            </ul>

          </div>
        </div>
      </div>
    </div>

  </div>
@endsection
