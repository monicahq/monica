{{-- The form recording a maintenance record of a possession: one job done on it. --}}
{{-- @var \App\ViewModels\Vaults\PossessionMaintenanceViewModel $viewModel --}}
@php
  $form = $viewModel->form();
  $value = fn (string $key, string $default = ''): string => (string) old($key, $form['values'][$key] ?? $default);
  $currency = \App\Enums\CurrencyEnum::tryFrom((string) old('currency')) ?? $form['currency'];
  $conditions = $viewModel->conditions();
@endphp

<x-form
  :method="$form['method']"
  :action="$form['action']"
  class="overflow-hidden rounded-lg border border-[color-mix(in_srgb,#9a6a24_45%,var(--hairline))] bg-canvas"
  aria-labelledby="maintenance-form-title"
>
  <div class="flex min-h-12 items-center gap-3 border-b border-[color-mix(in_srgb,#9a6a24_30%,var(--hairline))] bg-[color-mix(in_srgb,#9a6a24_9%,var(--canvas))] px-4 py-3.5">
    <span class="flex size-7 shrink-0 items-center justify-center rounded-md border border-[color-mix(in_srgb,#9a6a24_35%,var(--hairline))] bg-canvas text-[#7f571d] dark:text-[#d9a441]" aria-hidden="true">
      <x-icon :name="$form['isEditing'] ? 'note' : 'plus'" :size="15" />
    </span>
    <div class="min-w-0 flex-1">
      <h3 id="maintenance-form-title" class="text-card-title text-[#7f571d] dark:text-[#d9a441]">{{ $form['isEditing'] ? __('Edit maintenance record') : __('New maintenance record') }}</h3>
      <p class="text-xs text-muted">{{ __('Recorded on :name', ['name' => $viewModel->page()->name()]) }}</p>
    </div>
  </div>

  <div class="flex flex-col gap-4.5 p-4">
    <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] items-start gap-4">
      <x-select id="mnt-type" name="type" :label="__('Type')" required :error="$errors->get('type')">
        @foreach ($viewModel->types() as $option)
          <option value="{{ $option['value'] }}" @selected($value('type') === $option['value'])>{{ $option['label'] }}</option>
        @endforeach
      </x-select>
      <x-input id="mnt-title" name="title" :label="__('Title')" required :value="$value('title')" :placeholder="__('Re-plating, new gasket, annual check')" :error="$errors->get('title')" />
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
      <x-input id="mnt-by" name="performed_by" :label="__('Performed by')" optional :value="$value('performed_by')" :placeholder="__('A person or a workshop')" :error="$errors->get('performed_by')" />
      <x-input type="date" id="mnt-on" name="performed_on" :label="__('Performed')" required :max="$form['today']" :value="$value('performed_on')" :error="$errors->get('performed_on')" />
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(9.375rem,1fr))] gap-4">
      <x-input type="number" min="0" step="0.01" id="mnt-cost" name="cost" :label="__('Cost')" optional :value="$value('cost')" :error="$errors->get('cost')" />
      <x-currency-picker id="mnt-currency" name="currency" :label="__('Currency')" optional :value="$currency" :error="$errors->get('currency')" />
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
      @foreach (['condition_before' => __('Condition before'), 'condition_after' => __('Condition after')] as $key => $label)
        <x-select id="mnt-{{ $key }}" :name="$key" :label="$label" optional :error="$errors->get($key)">
          <option value="">{{ __('Not recorded') }}</option>
          @foreach ($conditions as $option)
            <option value="{{ $option['value'] }}" @selected($value($key) === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>
      @endforeach
    </div>

    <x-input type="date" id="mnt-due" name="next_due_on" :label="__('Next due')" optional :help="__('When this job should be done again.')" :value="$value('next_due_on')" :error="$errors->get('next_due_on')" />

    <x-textarea id="mnt-description" name="description" :label="__('Description')" optional :rows="3" :value="$value('description')" :placeholder="__('What was found and what was done')" :error="$errors->get('description')" />
  </div>

  <div class="flex flex-wrap gap-2 border-t border-hairline bg-card px-4 py-3">
    <x-button>{{ $form['isEditing'] ? __('Save changes') : __('Add record') }}</x-button>
    @if ($form['cancelUrl'] !== null)
      <x-button.secondary :href="$form['cancelUrl']">{{ __('Cancel') }}</x-button.secondary>
    @else
      <x-button.secondary type="button" @click="adding = false">{{ __('Cancel') }}</x-button.secondary>
    @endif
  </div>
</x-form>
