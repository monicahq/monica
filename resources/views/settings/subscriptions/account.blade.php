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
          <div class="pa3">

            <h3 class="mb3">{{ trans('settings.subscriptions_account_current_plan') }}</h3>

            <p>{{ trans('settings.subscriptions_account_current_paid_plan', ['name' => $planInformation]) }}</p>

            <p>{!! trans('settings.subscriptions_account_invoices', ['url' => $customerPortalUrl]) !!}</p>

            <div class="dt dt--fixed w-100 collapse br--top br--bottom">
              <div class="dt-row">
                <div class="dtc">
                  <div class="pa2 b">
                    {{ trans('settings.subscriptions_account_next_billing_title') }}
                  </div>
                </div>
                <div class="dtc w-60">
                  <div class="ph2">
                    {!! trans('settings.subscriptions_account_next_billing', ['date' => $nextBillingDate]) !!}
                  </div>
                </div>
                <div class="dtc {{ htmldir() == 'ltr' ? 'tr' : 'tl' }}">
                  <div class="pa2">
                    <a href="{{ $customerPortalUrl }}/monica">
                      {{ trans('settings.subscriptions_account_change') }}
                    </a>
                  </div>
                </div>
              </div>

              <div class="dt-row">
                <div class="dtc">
                  <div class="pa2 b">
                    {{ trans('settings.subscriptions_account_cancel_title') }}
                  </div>
                </div>
                <div class="dtc">
                  <div class="pa2">
                    {{ trans('settings.subscriptions_account_cancel') }}
                  </div>
                </div>
                <div class="dtc {{ htmldir() == 'ltr' ? 'tr' : 'tl' }}">
                  <div class="pa2">
                    <a href="{{ $customerPortalUrl }}/monica">
                      {{ trans('settings.subscriptions_account_cancel_action') }}
                    </a>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
