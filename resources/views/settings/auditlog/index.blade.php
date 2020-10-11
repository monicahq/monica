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
            <ul class="mb3">
              @foreach ($logsCollection as $log)
              <li class="pt2">
                {!! $log['description'] !!}
              </li>
              <li class="bb b--gray-monica pb2 f6">
                {{ trans('settings.logs_author', ['name' => $log['author_name'], 'date' => $log['audited_at']]) }}
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
