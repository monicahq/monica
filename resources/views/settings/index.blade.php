@extends('layouts.skeleton')

@section('content')

<div class="settings">

  {{-- Breadcrumb --}}
  <div class="breadcrumb">
    <div class="{{ auth()->user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-12">
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

      <div class="col-12 col-md-9">
        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            @include('partials.errors')

            @if (session('status'))
            <div class="alert alert-success">
              {{ session('status') }}
            </div>
            @endif

            <form action="{{ route('settings.save') }}" method="POST">
              @csrf

              {{-- id --}}
              <input type="hidden" name="id" value="{{ auth()->user()->id }}" />

              <h2>@lang('settings.title_general')</h2>
              <div class="pa2">
                {{-- names --}}
                <div class="form-group">
                  <label for="first_name" class="mb2 b">{{ trans('settings.firstname') }}</label>
                  <input type="text" class="form-control" name="first_name" id="first_name" required value="{{ auth()->user()->first_name }}">
                </div>

                <div class="form-group">
                  <label for="last_name" class="mb2 b">{{ trans('settings.lastname') }}</label>
                  <input type="text" class="form-control" name="last_name" id="last_name" required value="{{ auth()->user()->last_name }}">
                </div>

                {{-- email address --}}
                <div class="form-group">
                  <label for="email" class="mb2 b">{{ trans('settings.email') }}</label>
                  <input type="email" class="form-control" name="email" id="email" placeholder="{{ trans('settings.email_placeholder') }}" required value="{{ auth()->user()->email }}">
                  <small id="emailHelp" class="form-text text-muted">{{ trans('settings.email_help') }}</small>
                </div>

                <div class="form-group">
                  <label class="mb2 b">@lang('settings.me_title')</label>
                  <me-contact
                    :existing-contacts="{{ \Safe\json_encode($existingContacts) }}"
                    :contact="{{ \Safe\json_encode($meContact) }}"
                    :limited="{{ \Safe\json_encode($accountHasLimitations) }}"
                  >
                  </me-contact>
                  <small class="form-text text-muted">@lang('settings.me_help')</small>
                </div>
              </div>

              <h2 class="pt3">@lang('settings.title_i18n')</h2>
              <div class="pa2">
                {{-- Locale --}}
                <div class="form-group">
                  <label for="locale" class="mb2 b">{{ trans('settings.locale') }}</label>
                  <select class="form-control" name="locale" id="locale">
                    @foreach($locales as $locale)
                      <option value="{{ $locale['lang'] }}" {{ (auth()->user()->locale === $locale['lang'])?'selected':'' }}>
                        {{ $locale['name-orig'] }}
                        @if (auth()->user()->locale !== $locale['lang'] && $locale['name-orig'] !== $locale['name'])
                          — {{ $locale['name'] }}
                        @endif
                      </option>
                    @endforeach
                  </select>
                  <small class="form-text text-muted">{!! trans('settings.locale_help', ['url' => 'https://github.com/monicahq/monica/blob/master/docs/contribute/translate.md']) !!}</small>
                </div>

                {{-- currency for user --}}
                <div class="form-group">
                  <label for="currency_id" class="mb2 b">{{ trans('settings.currency') }}</label>
                  @include('partials.components.currency-select', ['selectionID' => auth()->user()->currency_id ])
                </div>

                {{-- Temperature scale --}}
                <div class="form-group">
                  <label for="temperature_scale" class="mb2 b">{{ trans('settings.temperature_scale') }}</label>
                  <select class="form-control" name="temperature_scale" id="temperature_scale">
                    <option value="fahrenheit" {{ (auth()->user()->temperature_scale == 'fahrenheit')?'selected':'' }}>{{ trans('settings.temperature_scale_fahrenheit') }}</option>
                    <option value="celsius" {{ (auth()->user()->temperature_scale == 'celsius')?'selected':'' }}>{{ trans('settings.temperature_scale_celsius') }}</option>
                  </select>
                </div>

                {{-- Reminder --}}
                <div class="form-group">
                  <reminder-time
                  :reminder="'{{ auth()->user()->account->default_time_reminder_is_sent }}'"
                  :timezone="'{{ $selectedTimezone }}'"
                  :timezones="{{ \Safe\json_encode($timezones) }}"
                  :hours="{{ \Safe\json_encode($hours) }}">
                  </reminder-time>
                </div>
              </div>

              <h2 class="pt3">@lang('settings.title_layout')</h2>
              <div class="pa2">
                {{-- Way of displaying names --}}
                <div class="form-group">
                  <label for="name_order" class="mb2 b">{{ trans('settings.name_order') }}</label>
                  <select id="name_order" name="name_order" class="form-control">
                    @foreach ($namesOrder as $nameOrder)
                    <option value="{{ $nameOrder }}" {{ (auth()->user()->name_order == $nameOrder) ? 'selected':'' }}>{{ trans('settings.name_order_'.$nameOrder) }}</option>
                    @endforeach
                  </select>
                </div>

                {{-- Layout --}}
                <div class="form-group">
                  <label for="layout" class="mb2 b">{{ trans('settings.layout') }}</label>
                  <select class="form-control" name="layout" id="layout">
                    <option value='false' {{ (auth()->user()->fluid_container == 'false')?'selected':'' }}>{{ trans('settings.layout_small') }}</option>
                    <option value='true' {{ (auth()->user()->fluid_container == 'true')?'selected':'' }}>{{ trans('settings.layout_big') }}</option>
                  </select>
                </div>
              </div>

              <button type="submit" class="btn btn-primary">{{ trans('settings.save') }}</button>
            </form>
          </div>
        </div>

        <form method="POST" action="{{ route('settings.reset') }}" class="settings-reset bg-white" onsubmit="return confirm('{{ trans('settings.reset_notice') }}')">
          @csrf

          <h2>{{ trans('settings.reset_title') }}</h2>
          <p>{{ trans('settings.reset_desc') }}</p>
          <button type="submit" class="btn">{{ trans('settings.reset_cta') }}</button>
        </form>

        <form method="POST" action="{{ route('settings.delete') }}" class="settings-delete bg-white" onsubmit="return confirm('{{ trans('settings.delete_notice') }}')">
          @csrf

          <h2>{{ trans('settings.delete_title') }}</h2>
          <p>{{ trans('settings.delete_desc') }}</p>
          <p>{{ trans('settings.delete_other_desc') }}</p>
          <button type="submit" class="btn">{{ trans('settings.delete_cta') }}</button>
        </form>

      </div>
    </div>
  </div>
</div>

@endsection
