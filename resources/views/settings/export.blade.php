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

      <div class="col-12 col-sm-9">

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            @include ('partials.errors')

            @if (session('status'))
              <div class="alert alert-success">
                {{ session('status') }}
              </div>
            @endif

            <h3>{{ trans('settings.export_title') }}</h3>
            <h4>{{ trans('settings.export_title_sql') }}</h4>
            <p>{{ trans('settings.export_sql_explanation') }}</p>
            <p>{{ trans('settings.export_be_patient') }}</p>
            <form action="{{ route('settings.export.store.sql') }}" method="POST">
              @csrf
              <p>
                <button type="submit" class="btn">{{ trans('settings.export_sql_cta') }}</button>
              </p>
            </form>
            <p>{!! trans('settings.export_sql_link_instructions', ['url' => 'https://github.com/monicahq/monica/blob/main/docs/installation/update.md#importing-sql-from-the-exporter-feature']) !!}</p>

            <h4>{{ trans('settings.export_title_json') }}</h4>
            <p>{{ trans('settings.export_json_explanation') }}</p>
            <div class="alert alert-success">
              {{ trans('settings.export_json_beta') }}
              <a href="https://github.com/monicahq/monica/discussions/5824" target="_blank" rel="noopener noreferrer">https://github.com/monicahq/monica/discussions/5824</a>
            </div>
            <form method="POST" action="{{ route('settings.export.store.json') }}">
              @csrf
              <p><button type="submit" class="btn">{{ trans('settings.export_json_cta') }}</button></p>
            </form>

            <h4>{{ trans('settings.export_last_title') }}</h4>
            @if ($exports->count() === 0)
              <em>{{ trans('settings.export_empty_title') }}</em>
            @else
              <ul class="table">
                <li class="table-row">
                  <div class="table-cell table-header">
                    {{ trans('settings.export_header_type') }}
                  </div>
                  <div class="table-cell table-header date">
                    {{ trans('settings.export_header_timestamp') }}
                  </div>
                  <div class="table-cell table-header">
                    {{ trans('settings.export_header_status') }}
                  </div>
                  <div class="table-cell table-header">
                    {{ trans('settings.export_header_actions') }}
                  </div>
                </li>
                @foreach ($exports as $export)
                <li class="table-row">
                  <div class="table-cell table-cell">
                    {{ trans("settings.export_type_{$export['type']}") }}
                  </div>
                  <div class="table-cell table-cell date">
                    {{ \App\Helpers\DateHelper::getShortDateWithTime($export['created_at']) }}
                  </div>
                  <div class="table-cell table-cell">
                    {{ trans("settings.export_status_{$export['status']}") }}
                  </div>
                  <div class="table-cell actions table-cell">
                    @if ($export['status'] === \App\Models\Account\ExportJob::EXPORT_DONE)
                      <form method="POST" action="{{ route('settings.export.download', ['uuid' => $export['uuid']]) }}">
                        @csrf
                        <a href="#" onclick="this.parentNode.submit(); return false">
                          {{ trans('app.download') }}
                        </a>
                      </form>

                    @endif
                  </div>
                </li>
                @endforeach
              </ul>
            @endif

          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
