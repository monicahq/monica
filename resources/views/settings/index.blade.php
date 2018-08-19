@extends('layouts.skeleton')

@section('content')

<div class="settings">

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ auth()->user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12">
          <ul class="horizontal">
            <li>
              <a href="{{ route('dashboard.index') }}">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_settings') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="{{ auth()->user()->getFluidLayout() }} mb4">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-md-9">
        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            @include('partials.errors')

            @if (session('status'))
              <div class="alert alert-success">
                  {{ session('status') }}
              </div>
            @endif

            <form action="{{ route('settings.save') }}" method="POST">
              {{ csrf_field() }}

              {{-- id --}}
              <input type="hidden" name="id" value="{{ auth()->user()->id }}" />

              {{-- names --}}
              <div class="form-group">
                <label for="first_name">{{ trans('settings.firstname') }}</label>
                <input type="text" class="form-control" name="first_name" id="first_name" required value="{{ auth()->user()->first_name }}">
              </div>

              <div class="form-group">
                <label for="last_name">{{ trans('settings.lastname') }}</label>
                <input type="text" class="form-control" name="last_name" id="last_name" required value="{{ auth()->user()->last_name }}">
              </div>

              {{-- email address --}}
              <div class="form-group">
                <label for="email">{{ trans('settings.email') }}</label>
                <input type="email" class="form-control" name="email" id="email" placeholder="{{ trans('settings.email_placeholder') }}" required value="{{ auth()->user()->email }}">
                <small id="emailHelp" class="form-text text-muted">{{ trans('settings.email_help') }}</small>
              </div>

              {{-- Locale --}}
              <div class="form-group">
                <label for="locale">{{ trans('settings.locale') }}</label>
                <select class="form-control" name="locale" id="locale">
                  @foreach($locales as $locale)
                    <option value="{{ $locale['lang'] }}" {{ (auth()->user()->locale == $locale['lang'])?'selected':'' }}>{{ $locale['name'] }}</option>
                  @endforeach
                </select>
              </div>

              {{-- currency for user --}}
              <div class="form-group">
                <label for="currency_id">{{ trans('settings.currency') }}</label>
                @include('partials.components.currency-select', ['selectionID' => auth()->user()->currency_id ])
              </div>

              {{-- Way of displaying names --}}
              <div class="form-group">
                <label for="name_order">{{ trans('settings.name_order') }}</label>
                <select id="name_order" name="name_order" class="form-control">
                  @foreach ($namesOrder as $nameOrder)
                  <option value="{{ $nameOrder }}" {{ (auth()->user()->name_order == $nameOrder) ? 'selected':'' }}>{{ trans('settings.name_order_'.$nameOrder) }}</option>
                  @endforeach
                </select>
              </div>

              {{-- Layout --}}
              <div class="form-group">
                <label for="layout">{{ trans('settings.layout') }}</label>
                <select class="form-control" name="layout" id="layout">
                  <option value='false' {{ (auth()->user()->fluid_container == 'false')?'selected':'' }}>{{ trans('settings.layout_small') }}</option>
                  <option value='true' {{ (auth()->user()->fluid_container == 'true')?'selected':'' }}>{{ trans('settings.layout_big') }}</option>
                </select>
              </div>

              {{-- Reminder --}}
              <div class="form-group">
                <reminder-time
                :reminder="'{{ auth()->user()->account->default_time_reminder_is_sent }}'"
                :timezone="'{{ $selectedTimezone }}'"
                :timezones="{{ json_encode($timezones) }}"
                :hours="{{ json_encode($hours) }}">
                </reminder-time>
              </div>

              <button type="submit" class="btn btn-primary">{{ trans('settings.save') }}</button>
            </form>
          </div>
        </div>

        <form method="POST" action="{{ route('settings.reset') }}" class="settings-reset bg-white" onsubmit="return confirm('{{ trans('settings.reset_notice') }}')">
          {{ csrf_field() }}

          <h2>{{ trans('settings.reset_title') }}</h2>
          <p>{{ trans('settings.reset_desc') }}</p>
          <button type="submit" class="btn">{{ trans('settings.reset_cta') }}</button>
        </form>

        <form method="POST" action="{{ route('settings.delete') }}" class="settings-delete bg-white" onsubmit="return confirm('{{ trans('settings.delete_notice') }}')">
          {{ csrf_field() }}

          <h2>{{ trans('settings.delete_title') }}</h2>
          <p>{{ trans('settings.delete_desc') }}</p>
          <button type="submit" class="btn">{{ trans('settings.delete_cta') }}</button>
        </form>

      </div>
    </div>
  </div>
</div>

@endsection
