{{-- Where a paying or formerly paying account stands: its access, what may be done, and what it uses. --}}
{{-- @var \App\ViewModels\Settings\Account\Billing\BillingViewModel $viewModel --}}
@php
  $summary = $viewModel->summary();
@endphp

<section aria-labelledby="billing-summary" class="rounded-lg border border-hairline bg-canvas shadow-panel">
  <div class="grid gap-6 p-7 md:grid-cols-[minmax(0,1fr)_auto] md:items-center">
    <div class="flex flex-col gap-2">
      <div><x-badge :tone="$summary['tone']">{{ $summary['badge'] }}</x-badge></div>

      <h2 id="billing-summary" class="mt-1 text-[40px] leading-[1.1] font-semibold tracking-[-0.04em] text-ink">{{ $summary['title'] }}</h2>

      <p class="max-w-[56ch] text-[16px] leading-normal text-pretty text-body">{{ $summary['line'] }}</p>
    </div>

    <div class="flex flex-col gap-2 md:min-w-50">
      @if ($viewModel->isEnding())
        <x-form method="delete" :action="route('settings.subscriptionCancellation.destroy')">
          <x-button class="w-full">{{ __('Keep the subscription') }}</x-button>
        </x-form>
      @elseif ($viewModel->isSubscribed())
        <x-modal.warning
          :title="__('Stop the subscription renewing?')"
          :confirm="__('Stop it renewing')"
          :action="route('settings.subscription.destroy')"
        >
          <x-slot:trigger>
            <x-button.secondary type="button" class="w-full" @click="open = true">{{ __('Cancel renewal') }}</x-button.secondary>
          </x-slot:trigger>

          {{ __('Full access stays until the end of the period already paid for, and nothing is deleted after it. You can subscribe again, or buy full access once, whenever you like.') }}
        </x-modal.warning>
      @elseif (! $viewModel->hasFullAccess() && $viewModel->offers() !== [])
        <x-button href="#plans" class="w-full">{{ __('Choose a plan') }}</x-button>
      @endif

      @if ($viewModel->isKnownToStripe() && $viewModel->hasFullAccess() && ! $viewModel->wasGranted())
        <x-button.secondary :href="route('settings.subscriptionPortal.show')" target="_blank" rel="noopener noreferrer" data-no-instant class="w-full">{{ __('See it on Stripe') }}</x-button.secondary>
      @endif

      @if ($viewModel->paymentCount() > 0)
        <x-button.secondary :href="route('settings.invoices.index')" data-turbo="true" class="w-full">{{ __('View invoices') }}</x-button.secondary>
      @endif
    </div>
  </div>

  <dl class="grid border-t border-hairline sm:grid-cols-3">
    @foreach ($viewModel->usage() as $item)
      <div @class(['flex flex-col gap-1.5 px-7 py-5', 'border-t border-hairline sm:border-t-0 sm:border-l' => ! $loop->first])>
        <dt><x-caption tone="muted">{{ $item['label'] }}</x-caption></dt>

        <dd class="text-[20px] font-semibold tracking-[-0.02em] text-ink">{{ $item['value'] }}</dd>

        @if ($item['percent'] !== null)
          <dd class="mt-1 h-1 overflow-hidden rounded-full bg-hairline" aria-hidden="true">
            <div class="h-full bg-ink" style="width: {{ $item['percent'] }}%"></div>
          </dd>
        @endif

        <dd class="text-base text-muted">{{ $item['note'] }}</dd>
      </div>
    @endforeach
  </dl>
</section>
