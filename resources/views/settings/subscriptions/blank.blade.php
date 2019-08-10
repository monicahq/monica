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

  <div class="main-content">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-12 col-sm-8 offset-sm-2">

          <h2 class="tc mt4 fw4">{{ trans('settings.subscriptions_account_upgrade_title') }}</h2>
          <p class="tc mb4">{{ trans('settings.subscriptions_account_upgrade_choice', ['customers' => $numberOfCustomers]) }}</p>

          <div class="br3 ba b--gray-monica bg-white mb4">
            <div class="pa4 bb b--gray-monica">

              <h3 class="tc">{{ trans('settings.subscriptions_account_payment') }}</h3>
              <div class="cf mb4">
                <div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-50-ns w-100 pa3 mt0-ns mt4">
                  <div class="b--purple ba pt3 br3 bw1 relative">
                    <img src="img/settings/subscription/best_value.png" class="absolute" style="top: -30px; left: -20px;">
                    <h3 class="tc mb3 pt3">{{ trans('settings.subscriptions_plan_year_title') }}</h3>
                    <p class="tc mb4">
                      <a href="settings/subscriptions/upgrade?plan=annual" class="btn btn-primary pv3">{{ trans('settings.subscriptions_plan_choose') }}</a>
                    </p>
                    <ul class="mb4 center ph4">
                      <li class="mb3 relative ml4">
                        <svg class="absolute" style="left: -30px; top: -3px;" width="26px" height="26px" viewBox="0 0 26 26" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                          <defs></defs>
                          <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g id="Group-7">
                              <circle id="Oval-14" fill="#836BC8" cx="13" cy="13" r="13"></circle>
                              <polyline id="Path-16" stroke="#FFFFFF" stroke-width="2" points="6.95703125 13.2783203 11.5048828 17.7226562 21.0205078 7.75"></polyline>
                            </g>
                          </g>
                        </svg>
                        <strong>{{ trans('settings.subscriptions_plan_year_cost') }}</strong>&nbsp;&ndash;&nbsp;{{ trans('settings.subscriptions_plan_year_cost_save') }}
                      </li>
                      <li class="mb3 relative ml4">
                        <svg class="absolute" style="left: -30px; top: -3px;" width="26px" height="26px" viewBox="0 0 26 26" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                          <defs></defs>
                          <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g id="Group-7">
                              <circle id="Oval-14" fill="#836BC8" cx="13" cy="13" r="13"></circle>
                              <polyline id="Path-16" stroke="#FFFFFF" stroke-width="2" points="6.95703125 13.2783203 11.5048828 17.7226562 21.0205078 7.75"></polyline>
                            </g>
                          </g>
                        </svg>
                        {{ trans('settings.subscriptions_plan_year_bonus') }}
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="{{ htmldir() == 'ltr' ? 'fl' : 'fr' }} w-50-ns w-100 pa3">
                  <div class="b--gray-monica ba pt3 br3 bw1">
                    <h3 class="tc mb3 pt3">{{ trans('settings.subscriptions_plan_month_title') }}</h3>
                    <p class="tc mb4">
                      <a href="settings/subscriptions/upgrade?plan=monthly" class="btn btn-primary pv3">{{ trans('settings.subscriptions_plan_choose') }}</a>
                    </p>
                    <ul class="mb4 center ph4">
                      <li class="mb3 relative ml4">
                        <svg class="absolute" style="left: -30px; top: -3px;" width="26px" height="26px" viewBox="0 0 26 26" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                          <defs></defs>
                          <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g id="Group-7">
                              <circle id="Oval-14" fill="#836BC8" cx="13" cy="13" r="13"></circle>
                              <polyline id="Path-16" stroke="#FFFFFF" stroke-width="2" points="6.95703125 13.2783203 11.5048828 17.7226562 21.0205078 7.75"></polyline>
                            </g>
                          </g>
                        </svg>
                        <strong>{{ trans('settings.subscriptions_plan_month_cost') }}</strong>
                      </li>
                      <li class="mb3 relative ml4">
                        <svg class="absolute" style="left: -30px; top: -3px;" width="26px" height="26px" viewBox="0 0 26 26" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                          <defs></defs>
                          <g id="App" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g id="Group-7">
                              <circle id="Oval-14" fill="#836BC8" cx="13" cy="13" r="13"></circle>
                              <polyline id="Path-16" stroke="#FFFFFF" stroke-width="2" points="6.95703125 13.2783203 11.5048828 17.7226562 21.0205078 7.75"></polyline>
                            </g>
                          </g>
                        </svg>
                        {{ trans('settings.subscriptions_plan_month_bonus') }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <p class="mb1 tc">{{ trans('settings.subscriptions_plan_include1') }}</p>
              <p class="mb1 tc">{{ trans('settings.subscriptions_plan_include2') }}</p>
              <p class="mb1 tc">{{ trans('settings.subscriptions_plan_include3') }}</p>
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
