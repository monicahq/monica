{{-- The newest payments on record, and the way to every invoice. --}}
{{-- @var \App\ViewModels\Settings\Account\Billing\BillingViewModel $viewModel --}}
<section aria-labelledby="billing-record" class="grid gap-8 border-t border-hairline pt-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] lg:gap-12">
  <div class="flex flex-col gap-3">
    <h2 id="billing-record" class="text-section-title text-ink">{{ __('On record') }}</h2>

    <p><x-link :href="route('settings.invoices.index')" data-turbo="true">{{ __('View invoices') }}</x-link></p>
  </div>

  <ul class="divide-y divide-hairline overflow-hidden rounded-lg border border-hairline">
    @foreach ($viewModel->recentPayments() as $payment)
      <li class="flex flex-wrap justify-between gap-x-4 gap-y-1 px-4 py-3 text-base">
        <x-timestamp :at="$payment['billed_at']" class="text-ink">{{ $payment['billed_at']->isoFormat('LL') }}</x-timestamp>

        <span class="text-body">{{ $payment['label'] }}</span>

        <span class="font-medium text-ink">{{ $payment['total'] }}</span>
      </li>
    @endforeach
  </ul>
</section>
