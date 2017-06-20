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
              <a href="/dashboard">{{ trans('app.breadcrumb_dashboard') }}</a>
            </li>
            <li>
              <a href="/settings">{{ trans('app.breadcrumb_settings') }}</a>
            </li>
            <li>
              {{ trans('app.breadcrumb_settings_subscriptions') }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="main-content modal subscriptions">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12 col-sm-6 col-sm-offset-3">

          @include ('partials.errors')

          <form action="/settings/subscriptions/processPayment" method="POST" id="payment-form">
            {{ csrf_field() }}
            <input type="hidden" name="planName" value="{{ config('monica.paid_plan_friendly_name') }}">

            <h2>{{ trans('settings.subscriptions_upgrade_title') }}</h2>

            <p>{!! trans('settings.subscriptions_upgrade_description') !!}</p>

            <div class="form-group">
              <label for="card-element" id="label-card-element">
                {{ trans('settings.subscriptions_upgrade_credit') }}
              </label>
              <div id="card-element">
              </div>
            </div>

            <!-- Used to display form errors -->
            <div id="card-errors" role="alert"></div>

            <div class="warning-zone">
              <p>{{ trans('settings.subscriptions_upgrade_warning') }}</p>
            </div>

            <div class="form-group actions">
              <button type="submit" class="btn btn-primary"><i class="fa fa-lock"></i>{{ trans('settings.subscriptions_upgrade_cta', ['price' => config('monica.paid_plan_price')]) }}</button>
              <a href="/settings" class="btn btn-secondary">{{ trans('app.cancel') }}</a>
            </div>

          </form>

        </div>
      </div>
    </div>
  </div>
</div>

@endsection
