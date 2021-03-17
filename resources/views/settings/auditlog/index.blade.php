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
              {{ trans('app.breadcrumb_settings_users') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-12 col-sm-9 users-list">

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">
            <h3>
              {{ trans('settings.logs_title') }}
            </h3>
            <ul class="table">
              <li class="table-row">
                <div class="table-cell table-header">
                  {{ trans('settings.logs_actor') }}
                </div>
                <div class="table-cell table-header date">
                  {{ trans('settings.logs_timestamp') }}
                </div>
                <div class="table-cell table-header">
                  {{ trans('settings.logs_description') }}
                </div>
                <div class="table-cell table-header">
                  {{ trans('settings.logs_subject') }}
                </div>
              </li>
              @foreach ($logsCollection as $log)
              <li class="table-row">
                <div class="table-cell audit-log-cell">
                  {{ $log['author_name'] }}
                </div>
                <div class="table-cell audit-log-cell date">
                  {{ \App\Helpers\DateHelper::getShortDateWithTime($log['audited_at']) }}
                </div>
                <div class="table-cell audit-log-cell">
                  {{ $log['description'] }}
                </div>
                <div class="table-cell audit-log-cell">
                  @if($log['link'])
                    <a href="{{ $log['link'] }}">{{ $log['object'] }}</a>
                  @else
                    {{ $log['object'] }}
                  @endif
                </div>
              </li>
              @endforeach
            </ul>

            <div class="tc center">
              {{ $logsPagination->links() }}
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
