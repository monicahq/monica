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

            <p>Thanks for being a subscriber.</p>
            <p>You can <strong>edit/cancel your plan</strong> or <strong>update your credit card information</strong> below.</p>

            <p>You will need to login using the email address you've used at the time you've subscried to Monica. Once you enter your email address, you will receive a unique code to your email address to confirm your identity. From there, you'll be able to manage your subscriptions.</p>

            <p class="mb5">
                <a href="{{ $customerPortalUrl }}" target="_blank">
                  Manage your subscription
                </a>
            </p>

            <p>Reminder: everything billing related is handled by Stripe, and we have no access to the credit card information at all.</p>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

@endsection
