{{-- Where a new possession is kept, and the address that spot takes from the place it is part of. --}}
{{-- @var \App\ViewModels\Vaults\PossessionFormViewModel $viewModel --}}
@php
  $locations = $viewModel->locations();
@endphp

<x-page-section :title="__('Where it is kept')">
  <div
    x-data="{
      locations: @js(collect($locations)->keyBy('uuid')),
      chosen: @js($viewModel->chosenLocation()),
      get place() { return this.locations[this.chosen] ?? null },
    }"
    class="flex flex-col gap-3.5"
  >
    <x-select id="location" :label="__('Location')" :error="[...$errors->get('location'), ...$errors->get('location_id')]" x-model="chosen" optional>
      <option value="">{{ __('Not recorded') }}</option>
      @foreach ($locations as $location)
        <option value="{{ $location['uuid'] }}" @selected($viewModel->chosenLocation() === $location['uuid'])>{{ $location['path'] }}</option>
      @endforeach
    </x-select>

    <template x-if="place && place.address">
      <div class="flex items-start gap-2.5 rounded-lg border border-hairline bg-card px-3.5 py-3" aria-live="polite">
        <x-icon name="place" :size="16" class="mt-0.5 text-muted" />
        <div class="text-sm">
          <p class="text-ink" x-text="place.address"></p>
          <p class="text-muted" x-text="place.addressNote"></p>
        </div>
      </div>
    </template>

    <p class="text-xs text-muted">
      {{ __('Missing a place?') }}
      <a href="{{ $viewModel->newLocationUrl() }}" class="focus-ring rounded-sm text-link underline decoration-hairline-strong underline-offset-2 hover:decoration-ink">{{ __('Add a location') }}</a>
    </p>
  </div>
</x-page-section>
