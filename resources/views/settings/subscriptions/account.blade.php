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

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-xs-12 col-sm-9 subscriptions">

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            <h3>{{ trans('settings.subscriptions_account_current_plan') }}</h3>

            <p>{!! trans('settings.subscriptions_account_current_paid_plan', ['name' => $planInformation['name']]) !!}</p>
            <p>{!! trans('settings.subscriptions_account_next_billing', ['date' => $nextBillingDate, 'url' => 'settings/subscriptions/downgrade']) !!}</p>

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
                      <a href="{{ route('settings.subscriptions.invoice', $invoice->id) }}">{{ trans('settings.subscriptions_account_invoices_download') }}</a>
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
  </div>
</div>

@endsection
