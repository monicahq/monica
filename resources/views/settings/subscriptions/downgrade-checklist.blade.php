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

  <div class="main-content modal subscriptions">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12 col-sm-6 col-sm-offset-3 downgrade">

          <h2>Downgrade your account to the free plan</h2>

          <p>The free plan has limitations. In order to be able to downgrade, you need to pass the checklist below:</p>

          <ul>

            <li class="{{ (auth()->user()->account->users()->count() > 1)?'fail':'success' }}">
              <span class="icon"></span>
              <span class="rule-title">You must have only 1 user in your account</span>
              <span class="rule-to-succeed">You currently have <a href="/settings/users">{{ auth()->user()->account->users()->count() }} users</a> in your account.</span>
            </li>

            <li class="{{ (auth()->user()->account->invitations()->count() > 0)?'fail':'success' }}">
              <span class="icon"></span>
              <span class="rule-title">You must not have pending invitations</span>
              <span class="rule-to-succeed">You currently have <a href="/settings/users/invitations">{{ auth()->user()->account->invitations()->count() }} pending invitations</a> sent to people.</span>
            </li>

          </ul>

          <form method="POST" action="/settings/subscriptions/downgrade">
            {{ csrf_field() }}

            @if (auth()->user()->account->canDowngrade())
            <p><button href="" class="btn btn-primary">Downgrade</button></p>
            @else
            <p><button class="btn btn-primary" disabled="disabled">Downgrade</button></p>
            @endif

          </form>

        </div>
      </div>
    </div>
  </div>
</div>

@endsection
