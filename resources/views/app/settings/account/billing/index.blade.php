{{-- What this account holds, what it could hold, and how to pay for it. --}}
{{-- @var \App\ViewModels\Settings\Account\Billing\BillingViewModel $viewModel --}}
<x-settings.page
  :title="__('Billing')"
  :user="$viewModel->user()"
  current="billing"
>
  <x-page-header :title="__('Billing')" :description="__('The offer this account is on, what it unlocks, and what is on record.')" />

  @if ($viewModel->isSelfHosted())
    <x-notice tone="info" :title="__('Nothing to pay for here')">
      {{ __('This instance is self hosted. Nothing is metered, and there is no offer to take up.') }}
    </x-notice>
  @else
    @include('app.settings.account.billing._tabs', ['current' => 'billing', 'paymentCount' => $viewModel->paymentCount()])

    @if ($viewModel->isAwaitingConfirmation())
      <x-notice tone="info" :title="__('Thank you, we are confirming your payment')">
        {{ __('Full access opens as soon as Stripe confirms the payment, usually within a minute. Reload this page to see it.') }}
      </x-notice>
    @endif

    @if (! $viewModel->offersAvailable())
      <x-notice tone="warning" :title="__('The offers cannot be shown right now')">
        {{ __('Stripe did not answer. Nothing is lost, come back in a moment or reload the page.') }}
      </x-notice>
    @endif

    @if ($errors->has('offer'))
      <x-notice tone="danger">{{ $errors->first('offer') }}</x-notice>
    @endif

    @if ($errors->has('subscription'))
      <x-notice tone="danger">{{ $errors->first('subscription') }}</x-notice>
    @endif

    <x-page-section :title="__('Your access')">
      <x-panel>
        <div class="flex flex-col gap-2">
          @if ($viewModel->holdsLifetimeAccess())
            <div><x-badge tone="success">{{ __('Lifetime access') }}</x-badge></div>

            <p class="text-sm text-pretty text-body">{{ __('Full access, for good. Nothing renews and nothing is capped.') }}</p>

            @if ($viewModel->isKnownToStripe())
              <p class="text-sm"><x-link :href="route('settings.subscriptionPortal.show')" target="_blank" rel="noopener noreferrer">{{ __('See it on Stripe') }}</x-link></p>
            @endif
          @elseif ($viewModel->isEnding())
            <div><x-badge tone="warning">{{ __('Subscription ending') }}</x-badge></div>

            <p class="text-sm text-pretty text-body">{{ __('Full access stays until :date. After that the account is back on the free offer, and nothing is deleted.', ['date' => $viewModel->endsAt()?->isoFormat('LL')]) }}</p>

            <p class="text-sm text-pretty text-body">{{ __('You can change your mind until then. Keep it, and it renews on :date as before.', ['date' => $viewModel->endsAt()?->isoFormat('LL')]) }}</p>

            <p class="text-sm"><x-link :href="route('settings.subscriptionPortal.show')" target="_blank" rel="noopener noreferrer">{{ __('See it on Stripe') }}</x-link></p>

            <div class="pt-1">
              <x-form method="delete" :action="route('settings.subscriptionCancellation.destroy')">
                <x-button size="small">{{ __('Keep the subscription') }}</x-button>
              </x-form>
            </div>
          @elseif ($viewModel->isSubscribed())
            <div><x-badge tone="success">{{ $viewModel->subscriptionLabel() }}</x-badge></div>

            <p class="text-sm text-pretty text-body">
              @if ($viewModel->renewsAt() !== null)
                {{ __('Full access for as long as the subscription runs. It renews on :date.', ['date' => $viewModel->renewsAt()->isoFormat('LL')]) }}
              @else
                {{ __('Full access for as long as the subscription runs.') }}
              @endif
            </p>

            <p class="text-sm"><x-link :href="route('settings.subscriptionPortal.show')" target="_blank" rel="noopener noreferrer">{{ __('See it on Stripe') }}</x-link></p>

            <div class="pt-1">
              <x-modal.warning
                :title="__('Stop the subscription renewing?')"
                :confirm="__('Stop it renewing')"
                :action="route('settings.subscription.destroy')"
              >
                <x-slot:trigger>
                  <x-button.secondary type="button" size="small" @click="open = true">{{ __('Cancel the subscription') }}</x-button.secondary>
                </x-slot:trigger>

                {{ __('Full access stays until the end of the period already paid for, and nothing is deleted after it. You can subscribe again, or buy full access once, whenever you like.') }}
              </x-modal.warning>
            </div>
          @elseif ($viewModel->accessEndedReason() !== null)
            <div><x-badge tone="danger">{{ __('Access ended') }}</x-badge></div>

            <p class="text-sm text-pretty text-body">{{ $viewModel->accessEndedReason() }}</p>
          @else
            <div><x-badge>{{ __('Free offer') }}</x-badge></div>

            <p class="text-sm text-pretty text-body">{{ __('Full access lifts the ceilings on vaults, contacts and storage. Buy it once, or subscribe.') }}</p>
          @endif
        </div>
      </x-panel>
    </x-page-section>

    <x-page-section :title="__('On record')" :description="__('What this account holds, and what was paid for it.')">
      <x-panel>
        <dl class="grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
          <div>
            <dt class="text-2xs font-medium tracking-caption text-muted-soft uppercase">{{ __('Access') }}</dt>
            <dd class="text-base text-ink">{{ $viewModel->accessKindLabel() }}</dd>
          </div>

          @if ($viewModel->purchasedAt() !== null && ! $viewModel->wasGranted())
            <div>
              <dt class="text-2xs font-medium tracking-caption text-muted-soft uppercase">{{ __('Bought on') }}</dt>
              <dd class="text-base text-ink">{{ $viewModel->purchasedAt()->isoFormat('LL') }}</dd>
            </div>
          @endif

          @if ($viewModel->renewsAt() !== null)
            <div>
              <dt class="text-2xs font-medium tracking-caption text-muted-soft uppercase">{{ __('Renews on') }}</dt>
              <dd class="text-base text-ink">{{ $viewModel->renewsAt()->isoFormat('LL') }}</dd>
            </div>
          @endif

          @if ($viewModel->endsAt() !== null && $viewModel->isEnding())
            <div>
              <dt class="text-2xs font-medium tracking-caption text-muted-soft uppercase">{{ __('Ends on') }}</dt>
              <dd class="text-base text-ink">{{ $viewModel->endsAt()->isoFormat('LL') }}</dd>
            </div>
          @endif

          @if ($viewModel->billingEmail() !== null)
            <div>
              <dt class="text-2xs font-medium tracking-caption text-muted-soft uppercase">{{ __('Billing address') }}</dt>
              <dd class="text-base text-ink">{{ $viewModel->billingEmail() }}</dd>
            </div>
          @endif

          @if ($viewModel->wasGranted())
            <div class="sm:col-span-2">
              <p class="text-sm text-pretty text-muted">{{ __('Full access was granted by us, so there is no payment and no invoice to show.') }}</p>
            </div>
          @endif

          <div>
            <dt class="text-2xs font-medium tracking-caption text-muted-soft uppercase">{{ __('Vaults') }}</dt>
            <dd class="text-base text-ink">{{ $viewModel->vaultsHeld() }}</dd>
          </div>

          <div>
            <dt class="text-2xs font-medium tracking-caption text-muted-soft uppercase">{{ __('Contacts') }}</dt>
            <dd class="text-base text-ink">{{ $viewModel->contactsHeld() }}</dd>
          </div>

          <div>
            <dt class="text-2xs font-medium tracking-caption text-muted-soft uppercase">{{ __('Storage') }}</dt>
            <dd class="text-base text-ink">{{ $viewModel->storageHeld() }}</dd>
          </div>
        </dl>
      </x-panel>
    </x-page-section>

    @if ($viewModel->offers() !== [])
      <x-page-section :title="__('Offers')" :description="__('All of them unlock exactly the same product, and every price includes tax. You pay at Stripe, never here.')">
        @if (count($viewModel->currencies()) > 1)
          <x-form method="put" :action="route('settings.billingCurrency.update')" class="flex flex-wrap items-end gap-2">
            <x-select id="currency" name="currency" :label="__('Currency')" size="small" class="w-40">
              @foreach ($viewModel->currencies() as $currency)
                <option value="{{ $currency }}" @selected($currency === $viewModel->currency())>{{ strtoupper($currency) }}</option>
              @endforeach
            </x-select>

            <x-button.secondary size="small">{{ __('Show') }}</x-button.secondary>
          </x-form>
        @endif

        <x-offer-list :offers="$viewModel->offers()" />
      </x-page-section>
    @endif
  @endif
</x-settings.page>
