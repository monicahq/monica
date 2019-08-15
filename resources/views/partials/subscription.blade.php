@if (($subscription = auth()->user()->account->getSubscribedPlan()) && $subscription->hasIncompletePayment())

<div class="alert alert-success">
  {!! trans('settings.subscriptions_account_confirm_payment', ['url' => route('cashier.payment', $subscription->latestPayment()->id)]) !!}
</div>

@endif
