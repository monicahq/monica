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

  <div class="main-content central-form subscriptions">
    <div class="{{ Auth::user()->getFluidLayout() }}">
      <div class="row">
        <div class="col-xs-12 col-sm-6 col-sm-offset-3 col-sm-offset-3-right downgrade">

          <h2>{{ trans('settings.subscriptions_downgrade_title') }}</h2>

          <p>{{ trans('settings.subscriptions_downgrade_limitations') }}</p>

          <ul>

            <li class="{{ (auth()->user()->account->users()->count() > 1)?'fail':'success' }}">
              <span class="icon"></span>
              <span class="rule-title">{{ trans('settings.subscriptions_downgrade_rule_users') }}</span>
              <span class="rule-to-succeed">{!! trans_choice('settings.subscriptions_downgrade_rule_users_constraint', auth()->user()->account->users()->count(), ['url' => '/settings/users', 'count' => auth()->user()->account->users()->count()]) !!}</span>
            </li>

            <li class="{{ (auth()->user()->account->invitations()->count() > 0)?'fail':'success' }}">
              <span class="icon"></span>
              <span class="rule-title">{{ trans('settings.subscriptions_downgrade_rule_invitations') }}</span>
              <span class="rule-to-succeed">{!! trans_choice('settings.subscriptions_downgrade_rule_invitations_constraint', auth()->user()->account->invitations()->count(), ['url' => '/settings/users', 'count' => auth()->user()->account->invitations()->count()]) !!}</span>
            </li>

            <li class="{{ (auth()->user()->account->hasReachedContactLimit() == true)?'fail':'success' }}">
              <span class="icon"></span>
              <span class="rule-title">{{ trans('settings.subscriptions_downgrade_rule_contacts', ['number' => config('monica.number_of_allowed_contacts_free_account')]) }}</span>
              <span class="rule-to-succeed">{!! trans_choice('settings.subscriptions_downgrade_rule_contacts_constraint', auth()->user()->account->contacts()->count(), ['url' => '/people', 'count' => auth()->user()->account->contacts()->count()]) !!}</span>
            </li>

          </ul>

          <form method="POST" action="{{ route('settings.subscriptions.downgrade') }}">
            {{ csrf_field() }}

            @if (auth()->user()->account->canDowngrade())
            <p class="mb4"><button href="" class="btn btn-primary">{{ trans('settings.subscriptions_downgrade_cta') }}</button></p>
            @else
            <p class="mb4"><button class="btn btn-primary" disabled="disabled">{{ trans('settings.subscriptions_downgrade_cta') }}</button></p>
            @endif

          </form>

        </div>
      </div>
    </div>
  </div>
</div>

@endsection
