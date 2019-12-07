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

  <div class="{{ Auth::user()->getFluidLayout() }}">
    <div class="row">

      @include('settings._sidebar')

      <div class="col-12 col-sm-9 subscriptions">

        <div class="br3 ba b--gray-monica bg-white mb4">
          <div class="pa3 bb b--gray-monica">

            <h3>{{ trans('settings.subscriptions_account_current_plan') }}</h3>

            <p>{{ trans('settings.subscriptions_account_current_paid_plan', ['name' => $planInformation['name']]) }}</p>

            @if ($subscription->hasIncompletePayment())
              @include('partials.subscription')
              @if (! app()->environment('production'))
              <p>
                <a href="{{ route('settings.subscriptions.forceCompletePaymentOnTesting') }}">
                  {{-- No translation needed --}}
                  Force payment success (test).
                </a>
              </p>
              @endif
            @else

            <p>{!! trans('settings.subscriptions_account_next_billing', ['date' => $nextBillingDate]) !!}</p>
            <p>{!! trans('settings.subscriptions_account_cancel', ['url' => route('settings.subscriptions.downgrade')]) !!}</p>

            {{-- Only display invoices if the subscription exists or existed --}}
            @if ($hasInvoices)
              <div class="invoices">
                <h3>{{ trans('settings.subscriptions_account_invoices') }}</h3>
                <ul class="table">
                  @foreach ($invoices as $invoice)
                  <li class="table-row" title="{{
                    trans('settings.subscriptions_account_invoices_subscription', [
                      'startDate' => \App\Helpers\DateHelper::getFullDate(Arr::first($invoice->subscriptions())->startDateAsCarbon()),
                      'endDate' => \App\Helpers\DateHelper::getFullDate(Arr::first($invoice->subscriptions())->endDateAsCarbon())
                    ])
                    }}">
                    <div class="table-cell date">
                      {{ \App\Helpers\DateHelper::getFullDate($invoice->date()) }}
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
            @endif

          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
