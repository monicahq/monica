{{-- Whether this instance can take a payment through Stripe, and what billing has brought in. --}}
{{-- @var \App\ViewModels\Admin\BillingStatusViewModel $viewModel --}}
<x-admin-layout :title="__('Billing')">
  <x-page-header :title="__('Billing')" :description="__('Whether this instance can take a payment through Stripe, whether Stripe confirms what it charged, and what billing has brought in.')">
    @unless ($viewModel->isSelfHosted())
      <x-slot:actions>
        <x-button.secondary :href="route('admin.billing.index')">{{ __('Check again') }}</x-button.secondary>
      </x-slot:actions>
    @endunless
  </x-page-header>

  @if ($viewModel->isSelfHosted())
    <p class="text-body">{{ __('This instance takes no payment, so there is nothing to show about Stripe.') }}</p>
  @else
    <div class="flex flex-col gap-6">
      <x-panel :title="__('What is on sale')" title-id="billing-offers-title" icon="credit-card" :flush="true">
        @if (! $viewModel->isReachable())
          <p class="px-4 py-3 text-sm text-body">{{ __('Stripe was not reached, so the catalogue was not read.') }}</p>
        @elseif (! $viewModel->isCatalogueAvailable())
          <p class="px-4 py-3 text-sm text-body">{{ __('The catalogue could not be read.') }}</p>
        @else
          @if ($viewModel->offers() === [])
            <p class="px-4 py-3 text-sm text-body">{{ __('Nothing is on sale.') }}</p>
          @else
            <x-scroll-region labelledby="billing-offers-title">
              <table class="w-full text-sm">
                <caption class="sr-only">{{ __('Each offer on sale and what it costs in each currency') }}</caption>
                <thead>
                  <tr class="border-b border-hairline text-left text-xs text-muted">
                    <th scope="col" class="px-4 py-2.5 font-medium">{{ __('Offer') }}</th>
                    <th scope="col" class="px-4 py-2.5 font-medium">{{ __('Amounts') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($viewModel->offers() as $offer)
                    <tr class="border-b border-hairline last:border-b-0">
                      <th scope="row" class="px-4 py-2.5 text-left font-normal text-ink">{{ $offer['kind'] }}</th>
                      <td class="px-4 py-2.5 text-ink">{{ implode(', ', $offer['amounts']) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </x-scroll-region>
          @endif

          @if ($viewModel->kindsWithoutPrice() !== [])
            <p class="border-t border-hairline px-4 py-3 text-sm text-ink">
              <x-badge tone="warning">{{ __('No price') }}</x-badge>
              {{ __('Nobody can buy these, since no price is on sale for them: :kinds.', ['kinds' => implode(', ', $viewModel->kindsWithoutPrice())]) }}
            </p>
          @endif
        @endif

        @if ($viewModel->isReachable())
          <div class="flex flex-wrap items-center justify-between gap-3 border-t border-hairline px-4 py-3">
            <p class="text-sm text-body">
              @if ($viewModel->catalogueReadAt() !== null)
                {{ __('Read from Stripe') }} <x-timestamp :at="$viewModel->catalogueReadAt()">{{ $viewModel->catalogueReadAt()->diffForHumans() }}</x-timestamp>.
              @endif
              {{ __('The instance reads it again once an hour.') }}
            </p>

            <x-form method="post" :action="route('admin.billing.catalogueReload.create')">
              <x-button.secondary type="submit" size="small">{{ __('Read the catalogue again') }}</x-button.secondary>
            </x-form>
          </div>
        @endif
      </x-panel>

      @php
        $events = $viewModel->events();
      @endphp

      <x-panel :title="__('Confirmations from Stripe')" title-id="billing-confirmations-title" icon="mail" :flush="true">
        <div class="flex flex-col gap-2 px-4 py-3 text-sm">
          @if ($viewModel->isReachable() && ! $viewModel->endpointsAreReadable())
            <p class="text-body">{{ __('The secret key may not read the confirmation endpoints, so they cannot be checked here.') }}</p>
          @elseif ($viewModel->endpointsAreReadable())
            @foreach ($viewModel->endpoints() as $endpoint)
              <p class="text-ink"><span class="font-mono break-all">{{ $endpoint['address'] }}</span> <x-badge :tone="$endpoint['isEnabled'] ? 'success' : 'neutral'">{{ $endpoint['state'] }}</x-badge></p>
            @endforeach

            @if (! $viewModel->hasEnabledEndpoint())
              <p class="text-ink"><x-badge tone="danger">{{ __('Missing') }}</x-badge> {{ __('Stripe holds no enabled endpoint for this instance.') }}</p>
            @endif

            @if ($viewModel->endpointsElsewhere() !== null)
              <p class="text-body">{{ $viewModel->endpointsElsewhere() }}</p>
            @endif

            @if ($viewModel->missingEvents() !== [])
              <p class="text-ink">{{ __('Stripe does not send these confirmations to this instance:') }}</p>
              <ul class="flex flex-col gap-1">
                @foreach ($viewModel->missingEvents() as $missing)
                  <li><code class="font-mono text-ink">{{ $missing['event'] }}</code> <span class="text-body">{{ $missing['consequence'] }}</span></li>
                @endforeach
              </ul>
            @elseif ($viewModel->hasEnabledEndpoint())
              <p class="text-body">{{ __('Stripe sends every confirmation this instance acts on.') }}</p>
            @endif
          @else
            <p class="text-body">{{ __('Stripe was not reached, so its endpoints were not read.') }}</p>
          @endif
        </div>

        @if ($events->isEmpty())
          <p class="border-t border-hairline px-4 py-3 text-sm text-body">{{ __('No confirmation arrived in the last thirty days.') }}</p>
        @else
          <p class="border-t border-hairline px-4 py-3 text-sm text-body">
            {{ __('The most recent confirmation was :kind,', ['kind' => $events->latest()['kind']]) }}
            <x-timestamp :at="$events->latest()['receivedAt']">{{ $events->latest()['receivedAt']->diffForHumans() }}</x-timestamp>.
          </p>

          <x-scroll-region labelledby="billing-confirmations-title">
            <table class="w-full text-sm">
              <caption class="sr-only">{{ __('How many confirmations of each kind arrived') }}</caption>
              <thead>
                <tr class="border-t border-b border-hairline text-left text-xs text-muted">
                  <th scope="col" class="px-4 py-2.5 font-medium">{{ __('Confirmation') }}</th>
                  <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Last 7 days') }}</th>
                  <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Last 30 days') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($events->countsByKind() as $row)
                  <tr class="border-b border-hairline last:border-b-0">
                    <th scope="row" class="px-4 py-2.5 text-left font-mono font-normal text-ink">{{ $row['kind'] }}</th>
                    <td class="px-4 py-2.5 text-right text-ink">{{ $row['lastSevenDays'] }}</td>
                    <td class="px-4 py-2.5 text-right text-ink">{{ $row['lastThirtyDays'] }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </x-scroll-region>
        @endif
      </x-panel>

      @php
        $figures = $viewModel->figures();
        $access = $figures->access();
        $subscriptions = $figures->subscriptions();
      @endphp

      <x-panel :title="__('What billing brings in')" icon="activity" :flush="true">
        <x-detail-list layout="stacked" label-style="caption">
          <x-detail-list.row :label="__('Full access')">
            <ul class="flex flex-col gap-0.5">
              <li>{{ trans_choice(':count account through a monthly subscription|:count accounts through a monthly subscription', $access['monthly']) }}</li>
              <li>{{ trans_choice(':count account through a yearly subscription|:count accounts through a yearly subscription', $access['yearly']) }}</li>
              @if ($access['kindNotKnown'] > 0)
                <li>{{ trans_choice(':count account through a subscription of a kind not known yet|:count accounts through a subscription of a kind not known yet', $access['kindNotKnown']) }}</li>
              @endif
              <li>{{ trans_choice(':count account through a purchase made once|:count accounts through a purchase made once', $access['lifetime']) }}</li>
              <li>{{ trans_choice(':count account through a grant by an administrator|:count accounts through a grant by an administrator', $access['granted']) }}</li>
              <li>{{ trans_choice(':count account without full access|:count accounts without full access', $access['none']) }}</li>
            </ul>
          </x-detail-list.row>

          <x-detail-list.row :label="__('Subscriptions')">
            <ul class="flex flex-col gap-0.5">
              <li>{{ trans_choice('[0,*]Active: :count', $subscriptions['active']) }}</li>
              <li>{{ trans_choice('[0,*]Cancelled, running until the end of their period: :count', $subscriptions['cancelledButRunning']) }}</li>
              <li>{{ trans_choice('[0,*]Waiting on a failed payment Stripe is retrying: :count', $subscriptions['pastDue']) }}</li>
            </ul>
          </x-detail-list.row>

          <x-detail-list.row :label="__('Money given back, last 30 days')">
            <ul class="flex flex-col gap-0.5">
              <li>{{ trans_choice('[0,*]Full refunds that ended access: :count', $figures->refunds()) }}</li>
              <li>{{ trans_choice('[0,*]Disputes that ended access: :count', $figures->disputes()) }}</li>
            </ul>
            <span class="mt-1 block text-sm text-body">{{ __('Partial refunds are not counted.') }}</span>
          </x-detail-list.row>
        </x-detail-list>

        @if (! $figures->hasAnyPayment())
          <p class="border-t border-hairline px-4 py-3 text-sm text-body">{{ __('No payment has been recorded yet.') }}</p>
        @else
          @foreach ($figures->payments() as $index => $month)
            <div class="border-t border-hairline">
              <h3 id="billing-payments-{{ $index }}" class="px-4 pt-3 text-sm font-medium text-ink">{{ __('Payments in :month', ['month' => $viewModel->monthLabel($month['month'])]) }}</h3>

              @if ($month['rows'] === [])
                <p class="px-4 py-3 text-sm text-body">{{ __('No payment this month.') }}</p>
              @else
                <x-scroll-region labelledby="billing-payments-{{ $index }}">
                  <table class="mt-2 w-full text-sm">
                    <caption class="sr-only">{{ __('Payments in :month', ['month' => $viewModel->monthLabel($month['month'])]) }}</caption>
                    <thead>
                      <tr class="border-b border-hairline text-left text-xs text-muted">
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('Offer') }}</th>
                        <th scope="col" class="px-4 py-2.5 font-medium">{{ __('Currency') }}</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Payments') }}</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Amount paid') }}</th>
                        <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Tax within it') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($month['rows'] as $row)
                        <tr class="border-b border-hairline last:border-b-0">
                          <th scope="row" class="px-4 py-2.5 text-left font-normal text-ink">{{ $viewModel->offerLabel($row['kind']) }}</th>
                          <td class="px-4 py-2.5 text-ink">{{ strtoupper($row['currency']) }}</td>
                          <td class="px-4 py-2.5 text-right text-ink">{{ $row['count'] }}</td>
                          <td class="px-4 py-2.5 text-right text-ink">{{ $viewModel->amount($row['total'], $row['currency']) }}</td>
                          <td class="px-4 py-2.5 text-right text-ink">{{ $viewModel->amount($row['tax'], $row['currency']) }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </x-scroll-region>
              @endif
            </div>
          @endforeach

          <p class="border-t border-hairline px-4 py-3 text-sm text-body">{{ __('Amounts are shown as invoiced, tax included. Refunds are not subtracted.') }}</p>
        @endif
      </x-panel>
    </div>
  @endif
</x-admin-layout>
