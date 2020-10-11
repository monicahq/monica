@extends('layouts.skeleton')

@section('content')

<div>

  <div class="mt4 mw6 center mb4 pa2 pa0-ns">
    <div class="br3 ba b--gray-monica bg-white pa4">
      @if (! $payment->isSucceeded() && ! $payment->isCancelled())
      <h2 class="tc mt2 fw4">{{ trans('settings.subscriptions_payment_confirm_title', ['amount' => $payment->amount()]) }}</h2>
      <p class="tc mb4">{{ trans('settings.subscriptions_payment_confirm_information') }}</p>
      @endif

      @include('partials.errors')
      <stripe-subscription
        :name="'{{ auth()->user()->name }}'"
        :stripe-key="'{{ config('cashier.key') }}'"
        :client-secret="'{{ $payment->clientSecret() }}'"
        :amount="'{{ $payment->amount() }}'"
        :confirm="true"
        :payment-succeeded="{{ \Safe\json_encode($payment->isSucceeded()) }}"
        :payment-cancelled="{{ \Safe\json_encode($payment->isCancelled()) }}"
        :callback="'{{ $redirect }}'"
        :token="'{{ csrf_token() }}'"
      ></stripe-subscription>

    </div>
  </div>
</div>

@endsection

@push('scripts')
  <script src="https://js.stripe.com/v3/"></script>
  <script src="{{ asset(mix('js/stripe.js')) }}"></script>
@endpush
