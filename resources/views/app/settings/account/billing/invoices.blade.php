{{-- Every payment the account made, each with the invoice Stripe issued for it. --}}
{{-- @var \App\ViewModels\Settings\Account\Billing\InvoicesViewModel $viewModel --}}
@php
  $payments = $viewModel->payments();
@endphp
<x-settings.page
  :title="__('Invoices')"
  :user="$viewModel->user()"
  current="billing"
  :back-href="route('settings.billing.index')"
  :back-label="__('Back to billing')"
>
  <x-page-header :title="__('Invoices')" :description="__('Every payment Stripe confirmed, newest first, with the invoice it issued.')" />

  <x-list-panel :items="$payments" :paginator="$payments" :size="$viewModel->size()" :default="$viewModel->defaultSize()">
    <ul class="divide-y divide-hairline">
      @foreach ($payments as $payment)
        <li class="flex flex-wrap items-baseline gap-x-4 gap-y-1 px-4 py-3.5">
          <x-timestamp :at="$payment->billed_at" class="text-sm text-muted">{{ $payment->billed_at->isoFormat('LL') }}</x-timestamp>

          <span class="text-base text-ink">{{ $viewModel->paymentLabel($payment) }}</span>

          <span class="text-base font-semibold text-ink">{{ $payment->total() }}</span>

          @if ($payment->hosted_invoice_url !== null)
            <x-link :href="route('settings.invoices.show', ['payment' => $payment->stripe_id])" data-turbo="false" data-no-instant class="ms-auto text-sm" target="_blank" rel="noopener noreferrer">
              {{ $payment->invoice_number !== null ? __('Invoice :number', ['number' => $payment->invoice_number]) : __('Invoice') }}
            </x-link>
          @else
            <span class="ms-auto text-sm text-muted">{{ __('No invoice') }}</span>
          @endif
        </li>
      @endforeach
    </ul>

    <x-slot:empty>
      <x-empty-state :title="__('No payment yet')">
        <x-slot:icon>
          <x-icon name="coin" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('Once a payment is confirmed it is listed here, with its invoice.') }}
      </x-empty-state>
    </x-slot:empty>
  </x-list-panel>
</x-settings.page>
