{{-- The form recording a valuation of a possession, saying as the amount is typed what it changes. --}}
{{-- @var \App\ViewModels\Vaults\PossessionValuationViewModel $viewModel --}}
@php
  $value = fn (string $key, string $default = ''): string => (string) old($key, $default);
  $currency = \App\Enums\CurrencyEnum::tryFrom((string) old('currency')) ?? $viewModel->preselectedCurrency();
  $type = $value('type', 'own_estimate');
  $confidence = $value('confidence', 'unknown');
@endphp

<x-form
  method="post"
  :action="$viewModel->createUrl()"
  :upload="$viewModel->canAttachDocuments()"
  class="overflow-hidden rounded-lg border border-[color-mix(in_srgb,#3f5fd0_45%,var(--hairline))] bg-canvas"
  aria-labelledby="valuation-form-title"
>
  <div
    x-data="{
      preview: @js($viewModel->preview()),
      amount: @js($value('amount')),
      date: @js($value('valued_on')),
      currency: @js($currency?->value),
      formatted(figure) {
        try {
          return new Intl.NumberFormat(document.documentElement.lang || undefined, { style: 'currency', currency: (this.currency || '').toUpperCase() }).format(figure)
        } catch (error) {
          return figure.toFixed(2)
        }
      },
      get sentence() {
        if (this.amount === '' || isNaN(Number(this.amount))) return this.preview.empty
        if (this.preview.amount === null) return this.preview.first
        if (this.date !== '' && this.date < this.preview.date) return this.preview.earlier
        if (this.currency !== this.preview.currency) return this.preview.otherCurrency
        const difference = Number(this.amount) - Number(this.preview.amount)
        if (difference === 0) return this.preview.unchanged
        return (difference > 0 ? this.preview.up : this.preview.down).replace(':difference', this.formatted(Math.abs(difference)))
      },
    }"
    @currency-chosen="currency = $event.detail.value"
    class="contents"
  >
    <div class="flex min-h-12 items-center gap-3 border-b border-[color-mix(in_srgb,#3f5fd0_30%,var(--hairline))] bg-[color-mix(in_srgb,#3f5fd0_9%,var(--canvas))] px-4 py-3.5">
      <span class="flex size-7 shrink-0 items-center justify-center rounded-md border border-[color-mix(in_srgb,#3f5fd0_35%,var(--hairline))] bg-canvas text-[#3f5fd0] dark:text-[#93a7f0]" aria-hidden="true">
        <x-icon name="activity" :size="15" />
      </span>
      <div class="min-w-0 flex-1">
        <h3 id="valuation-form-title" class="text-card-title text-[#3f5fd0] dark:text-[#93a7f0]">{{ __('New valuation') }}</h3>
        <p class="text-xs text-muted">{{ __('Recorded on :name. Once added, it is not edited.', ['name' => $viewModel->page()->name()]) }}</p>
      </div>
    </div>

    <div class="flex flex-col gap-4.5 p-4">
      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] items-start gap-4">
        <x-select id="val-type" name="type" :label="__('Type')" required :error="$errors->get('type')">
          @foreach ($viewModel->types() as $option)
            <option value="{{ $option['value'] }}" @selected($type === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>
        <x-input type="date" id="val-date" name="valued_on" :label="__('Date')" required :value="$value('valued_on')" :error="$errors->get('valued_on')" x-model="date" />
        <x-select id="val-confidence" name="confidence" :label="__('Confidence')" :help="__('How sure you are of this figure.')" :error="$errors->get('confidence')">
          @foreach ($viewModel->confidences() as $option)
            <option value="{{ $option['value'] }}" @selected($confidence === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>
      </div>

      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <x-input type="number" min="0" step="0.01" id="val-amount" name="amount" :label="__('Amount')" required :value="$value('amount')" :error="$errors->get('amount')" x-model="amount" />
        <x-currency-picker id="val-currency" name="currency" :label="__('Currency')" :value="$currency" :error="$errors->get('currency')" />
      </div>

      <p class="flex items-start gap-2 rounded-md border border-hairline bg-card px-3 py-2.5 text-sm text-pretty text-body" aria-live="polite">
        <span class="mt-0.5 flex text-muted" aria-hidden="true"><x-icon name="info" :size="14" /></span>
        <span x-text="sentence">{{ $viewModel->preview()['empty'] }}</span>
      </p>

      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <x-input id="val-valuer" name="valuer" :label="__('Valuer')" optional :value="$value('valuer')" :placeholder="__('A jeweller, an auction house, you')" :error="$errors->get('valuer')" />
        <x-input id="val-method" name="method" :label="__('Method')" optional :value="$value('method')" :placeholder="__('Comparable sales')" :error="$errors->get('method')" />
      </div>

      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <x-input id="val-reference" name="reference_number" :label="__('Reference number')" optional :value="$value('reference_number')" :error="$errors->get('reference_number')" />
        <x-input type="url" id="val-url" name="source_url" :label="__('Source link')" optional :value="$value('source_url')" :placeholder="__('Where this valuation can be checked')" :error="$errors->get('source_url')" />
      </div>

      <x-textarea id="val-note" name="note" :label="__('Note')" optional :rows="3" :value="$value('note')" :placeholder="__('Anything worth remembering about this valuation')" :error="$errors->get('note')" />

      @if ($viewModel->canAttachDocuments())
        <x-file-input id="val-document" name="document" :label="__('Certificate, listing or offer')" :help="__('Optional. Attach a certificate, a listing or any other file.')" :error="$errors->get('document')" />
      @endif
    </div>

    <div class="flex flex-wrap gap-2 border-t border-hairline bg-card px-4 py-3">
      <x-button>{{ __('Add valuation') }}</x-button>
      <x-button.secondary type="button" @click="adding = false">{{ __('Cancel') }}</x-button.secondary>
    </div>
  </div>
</x-form>
