{{-- The form recording a transaction of a possession, with its total worked out as figures are typed. --}}
{{-- @var \App\ViewModels\Vaults\PossessionTransactionsViewModel $viewModel --}}
@php
  $form = $viewModel->form();
  $labels = $viewModel->counterpartyLabels();
  $value = fn (string $key, string $default = ''): string => (string) old($key, $form['values'][$key] ?? $default);
  $currency = \App\Enums\CurrencyEnum::tryFrom((string) old('currency')) ?? $form['currency'];
  $type = $value('type', 'purchase');
@endphp

<x-form
  :method="$form['method']"
  :action="$form['action']"
  :upload="! $form['isEditing'] && $viewModel->canAttachDocuments()"
  class="overflow-hidden rounded-lg border border-[color-mix(in_srgb,#4f8a49_45%,var(--hairline))] bg-canvas"
  aria-labelledby="transaction-form-title"
>
  <div
    x-data="{
      type: @js($type),
      labels: @js($labels),
      amount: @js($value('amount')),
      tax: @js($value('tax')),
      fees: @js($value('fees')),
      shipping: @js($value('shipping')),
      total: @js($value('entered_total')),
      get figures() {
        return [['amount', this.amount], ['tax', this.tax], ['fees', this.fees], ['shipping', this.shipping]]
          .filter(([, value]) => value !== '' && ! isNaN(Number(value)))
      },
      get sum() {
        if (this.total !== '' && ! isNaN(Number(this.total))) return Number(this.total)
        if (this.figures.length === 0) return null
        return this.figures.reduce((sum, [, value]) => sum + Number(value), 0)
      },
      get how() {
        if (this.total !== '') return @js(__('as entered'))
        if (this.figures.length === 0) return @js(__('Unknown until a figure is entered'))
        return @js(__('the sum of the figures entered'))
      },
    }"
    class="contents"
  >
    <div class="flex min-h-12 items-center gap-3 border-b border-[color-mix(in_srgb,#4f8a49_30%,var(--hairline))] bg-[color-mix(in_srgb,#4f8a49_9%,var(--canvas))] px-4 py-3.5">
      <span class="flex size-7 shrink-0 items-center justify-center rounded-md border border-[color-mix(in_srgb,#4f8a49_35%,var(--hairline))] bg-canvas text-[#3f7239] dark:text-[#5faa6e]" aria-hidden="true">
        <x-icon :name="$form['isEditing'] ? 'note' : 'plus'" :size="15" />
      </span>
      <div class="min-w-0 flex-1">
        <h3 id="transaction-form-title" class="text-card-title text-[#3f7239] dark:text-[#5faa6e]">{{ $form['isEditing'] ? __('Edit transaction') : __('New transaction') }}</h3>
        <p class="text-xs text-muted">{{ __('Recorded on :name', ['name' => $viewModel->page()->name()]) }}</p>
      </div>
    </div>

    <div class="flex flex-col gap-4.5 p-4">
      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <x-select id="tx-type" name="type" :label="__('Type')" :error="$errors->get('type')" x-model="type">
          @foreach ($viewModel->types() as $group)
            <optgroup label="{{ $group['label'] }}">
              @foreach ($group['types'] as $option)
                <option value="{{ $option['value'] }}" @selected($type === $option['value'])>{{ $option['label'] }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </x-select>

        <x-input type="date" id="tx-date" name="occurred_on" :label="__('Date')" optional :value="$value('occurred_on')" :error="$errors->get('occurred_on')" />

        <div class="flex flex-col gap-1.5">
          <label for="tx-counterparty" class="flex items-baseline gap-1.5 text-label text-ink">
            <span x-text="labels[type] ?? @js(__('Counterparty'))">{{ $labels[$type] ?? __('Counterparty') }}</span>
            <span class="text-xs text-muted">{{ __('optional') }}</span>
          </label>
          <x-input id="tx-counterparty" name="counterparty" :value="$value('counterparty')" :placeholder="__('A shop, a person, an estate')" :error="$errors->get('counterparty')" />
        </div>
      </div>

      <div class="grid grid-cols-[repeat(auto-fit,minmax(9.375rem,1fr))] gap-4">
        <x-input type="number" min="0" step="0.01" id="tx-amount" name="amount" :label="__('Amount')" optional :error="$errors->get('amount')" :value="$value('amount')" x-model="amount" />
        <x-input type="number" min="0" step="0.01" id="tx-tax" name="tax" :label="__('Tax')" optional :error="$errors->get('tax')" :value="$value('tax')" x-model="tax" />
        <x-input type="number" min="0" step="0.01" id="tx-fees" name="fees" :label="__('Fees')" optional :error="$errors->get('fees')" :value="$value('fees')" x-model="fees" />
        <x-input type="number" min="0" step="0.01" id="tx-shipping" name="shipping" :label="__('Shipping')" optional :error="$errors->get('shipping')" :value="$value('shipping')" x-model="shipping" />
      </div>

      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <x-input type="number" min="0" step="0.01" id="tx-total" name="entered_total" :label="__('Total')" optional :help="__('Leave empty to add the four figures above together.')" :error="$errors->get('entered_total')" :value="$value('entered_total')" x-model="total" />
        <x-currency-picker id="tx-currency" name="currency" :label="__('Currency')" :value="$currency" :error="$errors->get('currency')" />
        <x-input id="tx-reference" name="reference" :label="__('Reference number')" optional :value="$value('reference')" :placeholder="__('Invoice 4021')" :error="$errors->get('reference')" />
      </div>

      <p class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5 rounded-md border border-hairline bg-card px-3 py-2.5 text-sm" aria-live="polite">
        <span class="text-muted">{{ __('Total of this transaction') }}</span>
        <b class="font-semibold text-ink tabular-nums" x-text="sum === null ? '—' : sum.toLocaleString(document.documentElement.lang || undefined, { minimumFractionDigits: sum % 1 ? 2 : 0, maximumFractionDigits: 2 })">—</b>
        <span class="text-muted" x-text="how"></span>
      </p>

      <x-input type="url" id="tx-source" name="source_url" :label="__('Source link')" optional :value="$value('source_url')" :placeholder="__('Where this transaction can be checked')" :error="$errors->get('source_url')" />

      <x-textarea id="tx-note" name="note" :label="__('Note')" optional :rows="3" :value="$value('note')" :placeholder="__('Anything worth remembering about this transaction')" :error="$errors->get('note')" />

      @if (! $form['isEditing'] && $viewModel->canAttachDocuments())
        <x-file-input id="tx-document" name="document" :label="__('Invoice or receipt')" :help="__('Optional. More can be attached once the transaction is recorded.')" :error="$errors->get('document')" />
      @endif
    </div>

    <div class="flex flex-wrap gap-2 border-t border-hairline bg-card px-4 py-3">
      <x-button>{{ $form['isEditing'] ? __('Save changes') : __('Add transaction') }}</x-button>
      @if ($form['cancelUrl'] !== null)
        <x-button.secondary :href="$form['cancelUrl']">{{ __('Cancel') }}</x-button.secondary>
      @else
        <x-button.secondary type="button" @click="adding = false">{{ __('Cancel') }}</x-button.secondary>
      @endif
    </div>
  </div>
</x-form>
