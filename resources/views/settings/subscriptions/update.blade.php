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
              {{ trans('app.breadcrumb_settings_subscriptions') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="main-content">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-12 col-sm-8 offset-sm-2">

          <h2 class="tc mt4 fw4">{{ trans('settings.subscriptions_account_update_title') }}</h2>

          <p class="tc mb4">{{ trans('settings.subscriptions_account_update_description') }}</p>

          @if ($legacyPlan)
            <div>
              <input type="radio" class="mr1" id="frequencycurrent" name="frequency" checked disabled>
              <label for="frequencycurrent" class="pointer">
                {{ trans('settings.subscriptions_account_current_legacy') }}
                {{ $legacyPlan['name'] }} – {{ $legacyPlan['friendlyPrice'] }}
              </label>
           </div>
          @endif

          <form action="{{ route('settings.subscriptions.update') }}" method="POST">
            @csrf

            @foreach ($plans as $plan)
              <div>
                <input type="radio" class="mr1" id="frequency{{ $plan['id'] }}" name="frequency" value="{{ $plan['type'] }}" @if($planInformation['id'] === $plan['id']) checked @endif>
                <label for="frequency{{ $plan['id'] }}" class="pointer">
                  {{ $plan['name'] }} – {{ $plan['friendlyPrice'] }}
                </label>
              </div>
            @endforeach

            <p class="ma3 alert alert-success">{{ trans('settings.subscriptions_account_update_information') }}</p>

            <div class="tc">
              <button type="submit" class="btn btn-primary">{{ trans('app.confirm') }}</button>
              <a href="{{ route('settings.subscriptions.index') }}" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

@endsection
