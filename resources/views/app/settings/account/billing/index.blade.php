{{-- What this account holds, what it could hold, and how to pay for it. --}}
{{-- @var \App\ViewModels\Settings\Account\Billing\BillingViewModel $viewModel --}}
<x-settings.page
  :title="__('Billing')"
  :user="$viewModel->user()"
  current="billing"
>
  <x-page-header :title="__('Billing')" />

  @if ($viewModel->isSelfHosted())
    <x-notice tone="info" :title="__('Nothing to pay for here')">
      {{ __('This instance is self hosted. Nothing is metered, and there is no plan to take up.') }}
    </x-notice>
  @else
    @if ($viewModel->isAwaitingConfirmation())
      <x-notice tone="info" :title="__('Thank you, we are confirming your payment')">
        {{ __('Full access opens as soon as Stripe confirms the payment, usually within a minute. Reload this page to see it.') }}
      </x-notice>
    @endif

    @if ($errors->has('subscription'))
      <x-notice tone="danger">{{ $errors->first('subscription') }}</x-notice>
    @endif

    @if ($viewModel->summary() !== null)
      @include('app.settings.account.billing._summary', ['viewModel' => $viewModel])
    @endif

    @if (! $viewModel->offersAvailable())
      <x-notice tone="warning" :title="__('The plans cannot be shown right now')">
        {{ __('Stripe did not answer. Nothing is lost, come back in a moment or reload the page.') }}
      </x-notice>
    @endif

    @if ($viewModel->offers() !== [])
      <section id="plans" aria-labelledby="plans-title" class="flex scroll-mt-20 flex-col gap-4 pt-4">
        @if ($viewModel->hasFullAccess())
          <h2 id="plans-title" class="text-[28px] leading-tight font-semibold tracking-[-0.03em] text-ink">{{ __('Other ways to pay') }}</h2>
        @else
          <div class="flex flex-col gap-3">
            <h2 id="plans-title" class="max-w-[20ch] text-[40px] leading-[1.1] font-semibold tracking-[-0.04em] text-balance text-ink">{{ __('If you like Monica and want to keep using it, it’s over there.') }}</h2>

            <p class="text-[16px] text-body">{{ __('No matter what, thanks for trying us out.') }}</p>
          </div>
        @endif

        @if ($errors->has('offer'))
          <x-notice tone="danger">{{ $errors->first('offer') }}</x-notice>
        @endif

        @if (count($viewModel->currencies()) > 1)
          <x-form method="put" :action="route('settings.billingCurrency.update')" data-turbo="true" class="flex flex-wrap items-center justify-end gap-3">
            <span id="currency-label" class="text-base text-muted">{{ __('Currency') }}</span>

            <div role="group" aria-labelledby="currency-label" class="flex flex-wrap gap-2">
              @foreach ($viewModel->currencies() as $currency)
                <button
                  type="submit"
                  name="currency"
                  value="{{ $currency }}"
                  aria-pressed="{{ $currency === $viewModel->currency() ? 'true' : 'false' }}"
                  class="focus-ring inline-flex h-9 cursor-pointer items-center gap-2 rounded-full border border-hairline bg-card pr-3.5 pl-2.5 text-base font-medium text-ink transition duration-(--duration-fast) ease-out hover:bg-hover aria-pressed:border-ink aria-pressed:bg-canvas aria-pressed:shadow-[0_0_0_1px_var(--ink)]"
                >
                  @include('app.settings.account.billing._flag', ['currency' => $currency])

                  {{ strtoupper($currency) }}
                </button>
              @endforeach
            </div>
          </x-form>
        @endif

        <x-offer-list :offers="$viewModel->offers()" :saving="$viewModel->yearlySaving()" />

        <div class="flex flex-wrap items-center justify-between gap-4 pt-1">
          <p class="text-base text-muted">{{ __('All prices include tax. Secure checkout through Stripe.') }}</p>

          @include('app.settings.account.billing._stripe-mark')
        </div>
      </section>
    @endif

    @unless ($viewModel->holdsLifetimeAccess())
      @include('app.settings.account.billing._benefits', ['viewModel' => $viewModel])
    @endunless

    @include('app.settings.account.billing._questions', ['viewModel' => $viewModel])

    @if ($viewModel->recentPayments() !== [])
      @include('app.settings.account.billing._record', ['viewModel' => $viewModel])
    @endif

    @include('app.settings.account.billing._about')
  @endif
</x-settings.page>
