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

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-sm-9 users-list">

        <h3>Your current plan</h3>

        @if (auth()->user()->account->subscribed(config('monica.paid_plan_friendly_name')))

        <p>You are on the {{ config('monica.paid_plan_friendly_name') }} plan. It costs ${{ config('monica.paid_plan_price') }} every month.</p>
        <p>Your subscription will auto-renew <strong>{{ $nextBillingdate }}</strong>. You can <a href="/settings/subscriptions/downgrade">cancel subscription</a> anytime.</p>

        @else

        <p>You are on the free plan.</p>

        @endif

        <div class="invoices">
          <h3>Invoices</h3>
          <ul class="table">
            @foreach ($account->invoices() as $invoice)
            <li class="table-row">
              <div class="table-cell date">
                {{ $invoice->date()->toFormattedDateString() }}
              </div>
              <div class="table-cell">
                {{ $invoice->total() }}
              </div>
              <div class="table-cell">
                <a href="/settings/subscriptions/invoice/{{ $invoice->id }}">Download</a>
              </div>
            </li>
            @endforeach
          </ul>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
