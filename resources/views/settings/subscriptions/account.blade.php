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

      <div class="col-xs-12 col-sm-9 subscriptions">

        <h3>{{ trans('settings.subscriptions_account_current_plan') }}</h3>

        @if (auth()->user()->account->subscribed(config('monica.paid_plan_friendly_name')))

        {{-- User is subscribed --}}
        <p>{{ trans('settings.subscriptions_account_paid_plan', ['name' => config('monica.paid_plan_friendly_name'), 'price' => config('monica.paid_plan_price')]) }}</p>
        <p>{!! trans('settings.subscriptions_account_next_billing', ['date' => auth()->user()->account->getNextBillingDate(), 'url' => '/settings/subscriptions/downgrade']) !!}</p>

        @else

        {{-- User was subscribed but not anymore --}}
        <p>{{ trans('settings.subscriptions_account_free_plan') }}</p>
        <p>{{ trans('settings.subscriptions_account_free_plan_upgrade', ['name' => config('monica.paid_plan_friendly_name'), 'price' => config('monica.paid_plan_price')]) }}</p>
        <ul class="upgrade-benefits">
          <li>{{ trans('settings.subscriptions_account_free_plan_benefits_users') }}</li>
          <li>{{ trans('settings.subscriptions_account_free_plan_benefits_support') }}</li>
        </ul>
        <p><a href="/settings/subscriptions/upgrade">{{ trans('settings.subscriptions_account_upgrade') }}</a></p>

        @endif

        {{-- Only display invoices if the subscription exists or existed --}}
        @if (auth()->user()->account->hasInvoices())
          <div class="invoices">
            <h3>{{ trans('settings.subscriptions_account_invoices') }}</h3>
            <ul class="table">
              @foreach (auth()->user()->account->invoices() as $invoice)
              <li class="table-row">
                <div class="table-cell date">
                  {{ $invoice->date()->toFormattedDateString() }}
                </div>
                <div class="table-cell">
                  {{ $invoice->total() }}
                </div>
                <div class="table-cell">
                  <a href="/settings/subscriptions/invoice/{{ $invoice->id }}">{{ trans('settings.subscriptions_account_invoices_download') }}</a>
                </div>
              </li>
              @endforeach
            </ul>
          </div>
        @endif

      </div>
    </div>
  </div>
</div>

@endsection
