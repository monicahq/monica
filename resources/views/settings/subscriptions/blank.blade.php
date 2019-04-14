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

  <div class="main-content">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12 col-sm-8 col-sm-offset-2">

          <h2 class="tc mt4 fw4">{{ trans('settings.subscriptions_account_upgrade_title') }}</h2>
          <p class="tc mb4">{{ trans('settings.subscriptions_account_upgrade_choice', ['customers' => $numberOfCustomers]) }}</p>

          <div class="br3 ba b--gray-monica bg-white mb4">
            <div class="pa4 bb b--gray-monica">

              <h3 class="tc">{{ trans('settings.subscriptions_account_payment') }}</h3>
              <p>We believe you don't need yet another subscription in your life. Subscriptions suck. Most of the $5/month services out there look cheap, but at after a few years, you end up paying a lot of money.</p>
              <p>We value your time and money. Monica is now offered as a <strong>one-time payment</strong>. Pay once, and enjoy the pro version of Monica forever. No complex cancellation process, no limitations.</p>
              <p>For a one time payment of $80, you will have an instant access to the premium version of Monica to enjoy:</p>

              <ul class="mb4">
                <li class="relative mb3 ml5">
                  <svg class="absolute" style="left: -30px; top: -3px;" width="26px" height="26px" viewBox="0 0 26 26" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs></defs>
                    <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                      <g id="Group-7">
                        <circle id="Oval-14" fill="#836BC8" cx="13" cy="13" r="13"></circle>
                        <polyline id="Path-16" stroke="#FFFFFF" stroke-width="2" points="6.95703125 13.2783203 11.5048828 17.7226562 21.0205078 7.75"></polyline>
                      </g>
                    </g>
                  </svg>
                  Unlimited number of contacts</li>
                <li class="relative mb3 ml5">
                  <svg class="absolute" style="left: -30px; top: -3px;" width="26px" height="26px" viewBox="0 0 26 26" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs></defs>
                    <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                      <g id="Group-7">
                        <circle id="Oval-14" fill="#836BC8" cx="13" cy="13" r="13"></circle>
                        <polyline id="Path-16" stroke="#FFFFFF" stroke-width="2" points="6.95703125 13.2783203 11.5048828 17.7226562 21.0205078 7.75"></polyline>
                      </g>
                    </g>
                  </svg>
                  Unlimited number of users</li>
                <li class="relative mb3 ml5">
                  <svg class="absolute" style="left: -30px; top: -3px;" width="26px" height="26px" viewBox="0 0 26 26" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs></defs>
                    <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                      <g id="Group-7">
                        <circle id="Oval-14" fill="#836BC8" cx="13" cy="13" r="13"></circle>
                        <polyline id="Path-16" stroke="#FFFFFF" stroke-width="2" points="6.95703125 13.2783203 11.5048828 17.7226562 21.0205078 7.75"></polyline>
                      </g>
                    </g>
                  </svg>
                  Reminders by email</li>
                <li class="relative mb3 ml5">
                  <svg class="absolute" style="left: -30px; top: -3px;" width="26px" height="26px" viewBox="0 0 26 26" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs></defs>
                    <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                      <g id="Group-7">
                        <circle id="Oval-14" fill="#836BC8" cx="13" cy="13" r="13"></circle>
                        <polyline id="Path-16" stroke="#FFFFFF" stroke-width="2" points="6.95703125 13.2783203 11.5048828 17.7226562 21.0205078 7.75"></polyline>
                      </g>
                    </g>
                  </svg>
                  Contact importing with vCard</li>
                <li class="relative mb3 ml5">
                  <svg class="absolute" style="left: -30px; top: -3px;" width="26px" height="26px" viewBox="0 0 26 26" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs></defs>
                    <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                      <g id="Group-7">
                        <circle id="Oval-14" fill="#836BC8" cx="13" cy="13" r="13"></circle>
                        <polyline id="Path-16" stroke="#FFFFFF" stroke-width="2" points="6.95703125 13.2783203 11.5048828 17.7226562 21.0205078 7.75"></polyline>
                      </g>
                    </g>
                  </svg>
                  Personalization of the contact sheet</li>
              </ul>

              <p class="tc mb1">
                <a href="settings/subscriptions/upgrade?plan=monthly" class="btn btn-primary pv3">Upgrade your account</a>
              </p>
              <p class="tc f6 mb0">For a one-time fee of $80.</p>
            </div>
          </div>

          <h3 class="tc mb4 mt3">{{ trans('settings.subscriptions_help_title') }}</h3>
          <h4>{{ trans('settings.subscriptions_help_opensource_title') }}</h4>
          <p class="mb4">{{ trans('settings.subscriptions_help_opensource_desc') }}</p>

          <h4>{{ trans('settings.subscriptions_help_limits_title') }}</h4>
          <p class="mb4">{{ trans('settings.subscriptions_help_limits_plan', ['number' => config('monica.number_of_allowed_contacts_free_account')]) }}</p>

          <h4>{{ trans('settings.subscriptions_help_discounts_title') }}</h4>
          <p class="mb4">{!! trans('settings.subscriptions_help_discounts_desc', ['support' => 'mailto:regis@monicahq.com']) !!}</p>

          <h4>{{ trans('settings.subscriptions_help_change_title') }}</h4>
          <p class="mb4">{{ trans('settings.subscriptions_help_change_desc') }}</p>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
