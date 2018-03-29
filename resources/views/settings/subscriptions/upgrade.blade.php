@extends('layouts.skeleton')

@section('content')

<div>

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

  <div class="mt4 mw6 center mb4 pa2 pa0-ns">
    <div class="br3 ba b--gray-monica bg-white pa4">
      <h2 class="tc mt2 fw4">{{ trans('settings.subscriptions_upgrade_choose', ['plan' => $planInformation['type']]) }}</h2>
      <p class="tc mb4">{{ trans('settings.subscriptions_upgrade_thanks') }}</p>
      <form action="/settings/subscriptions/processPayment" method="post" id="payment-form" class="mb4">
        {{ csrf_field() }}

        <input type="hidden" name="plan" value="{{ $planInformation['type'] }}">
        <div class="b--gray-monica ba pa4 br2 mb3 bg-black-05">
          <div class="form-row">
            <div class="mb3">
              <span>{{ trans('settings.subscriptions_upgrade_name') }}</span>
              <input name="cardholder-name" class="br3 b--black-30 ba pa3 w-100 f4" value="{{ auth()->user()->name }}" />
            </div>

            <div class="mb3">
              <span>{{ trans('settings.subscriptions_upgrade_zip') }}</span>
              <input name="address-zip" class="br3 b--black-30 ba pa3 w-100 f4" />
            </div>

            <div class="mb3" for="card-element">
                {{ trans('settings.subscriptions_upgrade_credit') }}
            </div>

            <div id="card-element">
              <!-- a Stripe Element will be inserted here. -->
            </div>

            <!-- Used to display Element errors -->
            <div id="card-errors" role="alert"></div>
          </div>
        </div>

        <button class="btn btn-primary w-100">{{ trans('settings.subscriptions_upgrade_submit') }}</button>
      </form>

      <p>{{ trans('settings.subscriptions_upgrade_charge', ['price' => $planInformation['friendlyPrice'], 'date' => $nextTheoriticalBillingDate]) }}</p>
      <p>{!! trans('settings.subscriptions_upgrade_charge_handled', ['url' => 'https://stripe.com']) !!}</p>
    </div>
  </div>
</div>

@endsection
