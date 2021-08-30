@if (($subscription = auth()->user()->account->getSubscribedPlan()) && $subscription->hasIncompletePayment())

<div class="alert alert-success">
  {!! trans('settings.subscriptions_account_confirm_payment', ['url' => route('settings.subscriptions.confirm', $subscription->latestPayment() ? $subscription->latestPayment()->id : '')]) !!}
</div>

@if (! app()->environment('production'))
<p>
  <a href="{{ route('settings.subscriptions.forceCompletePaymentOnTesting') }}">
    {{-- No translation needed --}}
    Force payment success (test).
  </a>
</p>
@endif

@endif
