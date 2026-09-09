{{-- Every payment the account made, each with the invoice Stripe issued for it. --}}
{{-- @var \App\ViewModels\Settings\Account\Billing\InvoicesViewModel $viewModel --}}
<x-shell :title="__('Invoices')">
  <x-slot:breadcrumb>
    <x-button.bar :href="route('settings.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="billing" />
  </x-slot:sidebar>

  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Invoices') }}</h1>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('Every payment Stripe confirmed, newest first, with the invoice it issued.') }}</p>
  </header>

  @include('app.settings.account.billing._tabs', ['current' => 'invoices', 'paymentCount' => $viewModel->payments()->count()])

  <x-panel :flush="true">
    @if ($viewModel->payments()->isEmpty())
      <x-empty-state :title="__('No payment yet')">
        <x-slot:icon>
          <x-icon name="coin" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('Once a payment is confirmed it is listed here, with its invoice.') }}
      </x-empty-state>
    @else
      <ul class="divide-y divide-hairline">
        @foreach ($viewModel->payments() as $payment)
          <li class="flex flex-wrap items-baseline gap-x-4 gap-y-1 px-4 py-3.5">
            <time datetime="{{ $payment->billed_at->toIso8601String() }}" class="text-sm text-muted">{{ $payment->billed_at->isoFormat('LL') }}</time>

            <span class="text-base text-ink">{{ $viewModel->paymentLabel($payment) }}</span>

            <span class="text-base font-semibold text-ink">{{ $payment->total() }}</span>

            @if ($payment->hosted_invoice_url !== null)
              <x-link :href="route('settings.invoices.show', ['payment' => $payment->stripe_id])" class="ml-auto text-sm">
                {{ $payment->invoice_number !== null ? __('Invoice :number', ['number' => $payment->invoice_number]) : __('Invoice') }}
              </x-link>
            @else
              <span class="ml-auto text-sm text-muted">{{ __('No invoice') }}</span>
            @endif
          </li>
        @endforeach
      </ul>
    @endif
  </x-panel>
</x-shell>
