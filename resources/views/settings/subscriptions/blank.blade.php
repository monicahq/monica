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

  <div class="main-content">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12 col-sm-8 col-sm-offset-2">

          <h2 class="tc mt4 fw4">Upgrade Monica today and have more meaningful relationships.</h2>
          <p class="tc mb4">Pick a plan below and join over {{ $numberOfCustomers }} persons who upgraded their Monica.</p>

          <div class="br3 ba b--gray-monica bg-white mb4">
            <div class="pa4 bb b--gray-monica">

              <h3 class="tc">Which payment option fits you best?</h3>
              <div class="cf mb4">
                <div class="fl w-50-ns w-100 pa3 mt0-ns mt4">
                  <div class="b--purple ba pt3 br3 bw1 relative">
                    <img src="{{ url('/img/settings/subscription/best_value.png') }}" class="absolute" style="top: -30px; left: -20px;">
                    <h3 class="tc mb3 pt3">Pay annually</h3>
                    <p class="tc mb4">
                      <a href="{{ url('/settings/subscriptions/upgrade?plan=annual') }}" class="btn btn-primary pv3">Choose this plan</a>
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
                        <strong>$45/year</strong> - you'll save 25%
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
                        Peace of mind for a whole year
                      </li>
                    </ul>
                  </div>
                </div>
                <div class="fl w-50-ns w-100 pa3">
                  <div class="b--gray-monica ba pt3 br3 bw1">
                    <h3 class="tc mb3 pt3">Pay monthly</h3>
                    <p class="tc mb4">
                      <a href="{{ url('/settings/subscriptions/upgrade?plan=monthly') }}" class="btn btn-primary pv3">Choose this plan</a>
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
                        <strong>$5/month</strong>
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
                        Cancel any time
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <p class="mb1 tc">Included with your upgrade:</p>
              <p class="mb1 tc">Unlimited number of users • Reminders by email • Import with vCard</p>
              <p class="mb1 tc">100% of the profits go the development of this great open source project.</p>
            </div>
          </div>

          <h3 class="tc mb4 mt3">Additional details you may be curious about</h3>
          <h4>What is an open source project?</h4>
          <p class="mb4">Monica is an open source project. This means it is built by an entirely benevolent community who just wants to provide a great tool for the greater good. Being open source means the code is publicly available on GitHub, and everyone can inspect it, modify it or enhance it. All the money we raise is dedicated to build better features, have more powerful servers, help pay the bills. Thanks for your help. We couldn't do it without you - litterally.</p>

          <h4>Is there any limit to the number of contacts we can have on the free plan?</h4>
          <p class="mb4">Absolutely not. Free plans do not have limitations on the number of contacts you can have.</p>

          <h4>Do you have discounts for non-profits and education?</h4>
          <p class="mb4">We do! Monica is free for students, and free for non-profits and charities. Just contact <a href="">the support</a> with a proof of your status and we'll apply this special status in your account.</p>

          <h4>What if I change my mind?</h4>
          <p class="mb4">You can cancel anytime, no questions asked, and all by yourself - no need to contact support or whatever. However, you will not be refunded for the current period.</p>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
