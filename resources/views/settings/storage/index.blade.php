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

            <h3>{{ trans('settings.storage_title') }}</h3>

            <p>{{ trans('settings.storage_account_info', ['accountLimit' => $accountLimit, 'currentAccountSize' => $currentAccountSize, 'percentUsage' => $percentUsage,]) }}</p>

            <p>{{ trans('settings.storage_description') }}</p>

            <ul class="table">
              <li class="table-row">
                <div class="table-cell table-header">
                  {{ trans('settings.logs_timestamp') }}
                </div>
                <div class="table-cell table-header">
                  {{ trans('settings.logs_object') }}
                </div>
                <div class="table-cell table-header">
                  {{ trans('settings.logs_size') }}
                </div>
                <div class="table-cell table-header">
                  {{ trans('settings.logs_subject') }}
                </div>
              </li>
              @foreach($elements as $element)
                <li class="table-row">
                  <div class="table-cell audit-log-cell">
                    {{ \App\Helpers\DateHelper::getShortDateWithTime($element->created_at) }}
                  </div>
                  <div class="table-cell audit-log-cell">
                    {{ $element->original_filename }}
                  </div>
                  <div class="table-cell audit-log-cell">
                    {{ round($element->filesize / 1000) }}
                  </div>
                  <div class="table-cell audit-log-cell">
                    @if ($element->contact())
                      @if ($element instanceof \App\Models\Contact\Document)
                        <a href="{{ route('people.show', ['contact' => $element->contact])}}">{{ $element->contact->name }}</a>
                      @else
                        <a href="{{ route('people.show', ['contact' => $element->contact()])}}">{{ $element->contact()->name }}</a>
                      @endif
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
</div>

@endsection
