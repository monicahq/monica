@extends('layouts.skeleton')

@section('content')

<div>

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

  <div class="mt4 mw6 center mb4 pa2 pa0-ns">
    <div class="br3 ba b--gray-monica bg-white pa4">
      <h2 class="tc mt2 fw4">{{ trans('settings.subscriptions_upgrade_choose', ['plan' => $planInformation['type']]) }}</h2>
      <p class="tc mb4">{{ trans('settings.subscriptions_upgrade_infos') }}</p>

      @include('partials.errors')
      <stripe-subscription
        :name="'{{ auth()->user()->name }}'"
        :stripe-key="'{{ config('cashier.key') }}'"
        :client-secret="'{{ $intent->client_secret }}'"
        :plan="'{{ $planInformation['type'] }}'"
        :amount="'{{ $planInformation['friendlyPrice'] }}'"
        :callback="'{{ route('settings.subscriptions.payment') }}'"
      ></stripe-subscription>

      <p>{{ trans('settings.subscriptions_upgrade_charge', ['price' => $planInformation['friendlyPrice'], 'date' => $nextTheoriticalBillingDate]) }}</p>
      <p>{!! trans('settings.subscriptions_upgrade_charge_handled', ['url' => 'https://stripe.com']) !!}</p>
    </div>
  </div>
</div>

@endsection

@push('scripts')
  <script src="https://js.stripe.com/v3/"></script>
  <script src="{{ asset(mix('js/stripe.js')) }}"></script>
@endpush
