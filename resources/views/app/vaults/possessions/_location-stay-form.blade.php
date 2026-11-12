{{-- The form moving a possession elsewhere, or correcting one of its stays. --}}
{{-- @var \App\ViewModels\Vaults\PossessionLocationsViewModel $viewModel --}}
@php
  $form = $viewModel->form();
  $value = fn (string $key, string $default = ''): string => (string) old($key, $form['values'][$key] ?? $default);
  $preview = $viewModel->preview();
  $location = $value('location_id');
@endphp

<x-form
  :method="$form['method']"
  :action="$form['action']"
  class="overflow-hidden rounded-lg border border-hairline bg-canvas"
  aria-labelledby="location-stay-form-title"
>
  <div
    x-data="{
      location: @js($location),
      date: @js($value('moved_on')),
      current: @js($preview['currentLocation']),
      names: @js($viewModel->locationNames()),
      wordings: @js($preview),
      get preview() {
        if (! this.location || ! this.date) return this.wordings.empty
        const name = this.names[this.location] ?? ''
        return (this.current ? this.wordings.closes : this.wordings.opens).replace(':new', name)
      },
    }"
    class="contents"
  >
    <div class="flex min-h-12 items-center gap-3 border-b border-hairline bg-card px-4 py-3.5">
      <span class="flex size-7 shrink-0 items-center justify-center rounded-md border border-hairline bg-canvas text-[#26686e] dark:text-[#5fb3bb]" aria-hidden="true">
        <x-icon :name="$form['isEditing'] ? 'note' : 'place'" :size="15" />
      </span>
      <div class="min-w-0 flex-1">
        <h3 id="location-stay-form-title" class="text-card-title text-ink">{{ $form['title'] }}</h3>
        <p class="text-xs text-muted">{{ $form['subtitle'] }}</p>
      </div>
    </div>

    <div class="flex flex-col gap-4.5 p-4">
      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <x-select id="location-stay-location" name="location_id" :label="$form['isEditing'] ? __('Location') : __('Move to')" :error="$errors->get('location_id')" x-model="location">
          <option value="">{{ __('Choose a location') }}</option>
          @foreach ($viewModel->locationOptions() as $option)
            <option value="{{ $option['value'] }}" @selected($location === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>

        @if ($form['isEditing'])
          <x-input type="date" id="location-stay-arrived" name="arrived_on" :label="__('Arrived')" optional :value="$value('arrived_on')" :help="__('Leave it empty when the day is unknown.')" :error="$errors->get('arrived_on')" />
          @if (! $form['isOpenStay'])
            <x-input type="date" id="location-stay-left" name="left_on" :label="__('Left')" :value="$value('left_on')" :error="$errors->get('left_on')" />
          @endif
        @else
          <x-input type="date" id="location-stay-moved" name="moved_on" :label="__('Moved on')" :value="$value('moved_on')" x-model="date" :error="$errors->get('moved_on')" />
        @endif
      </div>

      <x-input id="location-stay-reason" name="reason" :label="__('Reason')" optional :value="$value('reason')" :placeholder="__('Appraisal, safekeeping while away, a new shelf')" :error="$errors->get('reason')" />

      <x-textarea id="location-stay-note" name="note" :label="__('Note')" optional :rows="2" :value="$value('note')" :placeholder="__('Anything worth knowing about this stay')" :error="$errors->get('note')" />

      @if ($form['isEditing'])
        <p class="text-xs text-pretty text-muted">{{ __('Changing this stay does not move the stays before or after it. Correct those separately if their dates change too.') }}</p>
      @else
        <p class="text-xs text-pretty text-muted" aria-live="polite" x-text="preview">{{ $preview['empty'] }}</p>
      @endif
    </div>

    <div class="flex flex-wrap gap-2 border-t border-hairline bg-card px-4 py-3">
      <x-button>{{ $form['isEditing'] ? __('Save changes') : __('Move it') }}</x-button>
      @if ($form['cancelUrl'] !== null)
        <x-button.secondary :href="$form['cancelUrl']">{{ __('Cancel') }}</x-button.secondary>
      @else
        <x-button.secondary type="button" @click="moving = false">{{ __('Cancel') }}</x-button.secondary>
      @endif
    </div>
  </div>
</x-form>
