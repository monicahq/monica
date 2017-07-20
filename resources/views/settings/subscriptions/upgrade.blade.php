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

  <div class="main-content central-form subscriptions">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12 col-sm-6 col-sm-offset-3">

          @include ('partials.errors')

          <form action="/settings/subscriptions/processPayment" method="POST" id="payment-form">
            {{ csrf_field() }}

            <h2>{{ trans('settings.subscriptions_upgrade_title') }}</h2>

            <p>{!! trans('settings.subscriptions_upgrade_description') !!}</p>

            <script
              src="https://checkout.stripe.com/checkout.js" class="stripe-button"
              data-key="{{ config('services.stripe.key') }}"
              data-amount="{{ config('monica.paid_plan_price') }}"
              data-name="Monica"
              data-email="{{ auth()->user()->email }}"
              data-description="Widget"
              data-image="https://s3.amazonaws.com/stripe-uploads/adK6oPZbepKr0KJ70S42c01UPm0HLFL2merchant-icon-1497151424388-Group.png"
              data-locale="auto"
              data-currency="usd">
            </script>

            <div class="warning-zone">
              <p>{{ trans('settings.subscriptions_upgrade_warning') }}</p>
            </div>

          </form>

        </div>
      </div>
    </div>
  </div>
</div>

@endsection
