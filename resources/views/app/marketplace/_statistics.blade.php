{{-- The band across the top of the catalogue: four counts and a year of installations. --}}
{{-- @var \App\ViewModels\Marketplace\MarketplaceStatistics $statistics --}}
@php
  $months = $statistics->installationsPerMonth();
  $tile = 'flex flex-col gap-0.5 bg-canvas px-4.5 py-4';
@endphp

<section class="flex flex-col gap-4 lg:flex-row lg:items-stretch">
  <div class="grid flex-1 grid-cols-1 gap-px overflow-hidden rounded-lg border border-hairline bg-hairline sm:grid-cols-2">
    <div class="{{ $tile }}">
      <span class="text-page-title text-ink">{{ $statistics->listingCount() }}</span>
      <span class="text-sm font-semibold text-ink">{{ __('listings published') }}</span>
      <span class="text-xs text-muted">{{ $statistics->listingBreakdown() }}</span>
    </div>

    <div class="{{ $tile }}">
      <span class="text-page-title text-ink">{{ $statistics->installationCount() }}</span>
      <span class="text-sm font-semibold text-ink">{{ __('installations') }}</span>
      <span class="text-xs text-muted">{{ $statistics->recentInstallationLabel() }}</span>
    </div>

    <div class="{{ $tile }}">
      <span class="text-page-title text-ink">{{ $statistics->publisherCount() }}</span>
      <span class="text-sm font-semibold text-ink">{{ __('people publishing') }}</span>
      <span class="text-xs text-muted">{{ $statistics->recentPublisherLabel() }}</span>
    </div>

    <div class="{{ $tile }}">
      <span class="text-page-title text-ink">{{ $statistics->languageCount() }}</span>
      <span class="text-sm font-semibold text-ink">{{ __('languages') }}</span>
      <span class="text-xs text-muted">{{ $statistics->languageSummary() }}</span>
    </div>
  </div>

  <div class="flex w-full flex-col gap-3.5 rounded-lg border border-hairline bg-canvas px-4.5 py-4 lg:w-105 lg:shrink-0">
    <div class="flex items-baseline gap-2">
      <span class="text-sm font-semibold text-ink">{{ __('Installations per month') }}</span>
      <span class="ml-auto text-xs text-muted">{{ __('Last twelve months') }}</span>
    </div>

    <div class="grid min-h-26 flex-1 grid-cols-12 items-end gap-1.5" aria-hidden="true">
      @foreach ($months as $month)
        <span
          @class([
            'rounded-t-sm',
            'bg-ink' => $month['isCurrent'],
            'bg-hover' => ! $month['isCurrent'],
          ])
          style="height: {{ $month['height'] }}%;"
        ></span>
      @endforeach
    </div>

    <div class="flex justify-between border-t border-hairline pt-2 text-xs text-muted">
      <span>{{ $months[0]['label'] }}</span>
      <span class="text-ink">{{ $statistics->currentMonthLabel() }}</span>
      <span>{{ $months[count($months) - 1]['label'] }}</span>
    </div>
  </div>
</section>
