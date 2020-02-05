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
            <h3 class="with-actions">
              {{ trans('settings.users_list_title') }}
              <a href="{{ route('settings.users.create') }}" class="btn">{{ trans('settings.users_list_add_user') }}</a>
            </h3>
            <ul>
              @foreach ($logs as $log)
              <li>
                {{ $log['author_name'] }}

                {!! $log['description'] !!}

                {{ $log['audited_at'] }}
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
