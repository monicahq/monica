{{-- The strip under the top bar showing a free account its limits, until its reader hides it for good. --}}
{{-- @var \App\ViewModels\FullAccessOfferViewModel $viewModel --}}
@props([
  'viewModel',
])

<section
  aria-label="{{ __('Free plan usage') }}"
  class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-hairline bg-card px-4 py-2.5 text-base sm:px-6 print:hidden"
>
  <x-badge tone="info" class="uppercase tracking-wide">{{ __('Free plan') }}</x-badge>

  @foreach ($viewModel->meters() as $meter)
    @php
      $trackColour = match ($meter['tone']) {
        'danger' => 'bg-error-edge',
        'warning' => 'bg-warning-edge',
        default => 'bg-hairline',
      };

      $fillColour = match ($meter['tone']) {
        'danger' => 'bg-error',
        'warning' => 'bg-warning',
        default => 'bg-ink',
      };

      $figureColour = match ($meter['tone']) {
        'danger' => 'text-error',
        'warning' => 'text-warning',
        default => 'text-ink',
      };
    @endphp

    <div class="flex items-center gap-2.5">
      <span id="full-access-offer-meter-{{ $loop->index }}" aria-hidden="true" class="text-body">{{ $meter['label'] }}</span>

      <div
        role="progressbar"
        aria-labelledby="full-access-offer-meter-{{ $loop->index }}"
        aria-valuemin="0"
        aria-valuemax="{{ $meter['limit'] }}"
        aria-valuenow="{{ min($meter['used'], $meter['limit']) }}"
        aria-valuetext="{{ $meter['description'] }}"
        class="h-1 w-16 overflow-hidden rounded-full sm:w-30 {{ $trackColour }}"
      >
        <div class="h-full {{ $fillColour }}" style="width: {{ $meter['percent'] }}%"></div>
      </div>

      <span aria-hidden="true" class="font-medium tabular-nums {{ $figureColour }}">{{ $meter['used'] }} / {{ $meter['limit'] }}</span>
    </div>
  @endforeach

  <div class="ms-auto flex items-center gap-2">
    <x-button.secondary :href="route('settings.billing.index')" data-turbo="true" size="small">
      {{ __('Upgrade your account') }}
    </x-button.secondary>

    <x-form method="put" :action="route('fullAccessOffer.update')">
      <x-icon-button type="submit" icon="close" align="right" :label="__('Hide this offer for good')" />
    </x-form>
  </div>
</section>
