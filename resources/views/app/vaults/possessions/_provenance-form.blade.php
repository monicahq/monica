{{-- The form recording a provenance event of a possession, its date field shaped by its precision. --}}
{{-- @var \App\ViewModels\Vaults\PossessionProvenanceViewModel $viewModel --}}
@php
  $form = $viewModel->form();
  $value = fn (string $key, string $default = ''): string => (string) old($key, $form['values'][$key] ?? $default);
  $type = $value('type', 'acquisition');
  $precision = $value('date_precision', 'day');
  $precisions = $viewModel->precisions();
  $placeholders = $viewModel->titlePlaceholders();
  $transaction = $value('transaction');
  $isVerified = (bool) old('is_verified', $form['isVerified']);
@endphp

<x-form
  :method="$form['method']"
  :action="$form['action']"
  class="overflow-hidden rounded-lg border border-[color-mix(in_srgb,#6b4fa8_45%,var(--hairline))] bg-canvas"
  aria-labelledby="provenance-form-title"
>
  <div
    x-data="{
      type: @js($type),
      precision: @js($precision),
      date: @js($value('occurred_on')),
      placeholders: @js($placeholders),
      help: @js(array_column($precisions, 'help', 'value')),
    }"
    class="contents"
  >
    <div class="flex min-h-12 items-center gap-3 border-b border-[color-mix(in_srgb,#6b4fa8_30%,var(--hairline))] bg-[color-mix(in_srgb,#6b4fa8_9%,var(--canvas))] px-4 py-3.5">
      <span class="flex size-7 shrink-0 items-center justify-center rounded-md border border-[color-mix(in_srgb,#6b4fa8_35%,var(--hairline))] bg-canvas text-[#5b4192] dark:text-[#b49ae4]" aria-hidden="true">
        <x-icon :name="$form['isEditing'] ? 'note' : 'plus'" :size="15" />
      </span>
      <div class="min-w-0 flex-1">
        <h3 id="provenance-form-title" class="text-card-title text-[#5b4192] dark:text-[#b49ae4]">{{ $form['isEditing'] ? __('Edit provenance event') : __('New provenance event') }}</h3>
        <p class="text-xs text-muted">{{ $form['isEditing'] ? __('Recorded on :name', ['name' => $viewModel->page()->name()]) : __('Added to the chain in date order') }}</p>
      </div>
    </div>

    <div class="flex flex-col gap-4.5 p-4">
      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <x-select id="provenance-type" name="type" :label="__('Type')" :error="$errors->get('type')" x-model="type">
          @foreach ($viewModel->types() as $group)
            <optgroup label="{{ $group['label'] }}">
              @foreach ($group['types'] as $option)
                <option value="{{ $option['value'] }}" @selected($type === $option['value'])>{{ $option['label'] }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </x-select>

        <x-input id="provenance-title" name="title" :label="__('Title')" optional :value="$value('title')" :placeholder="$placeholders[$type] ?? ''" x-bind:placeholder="placeholders[type] ?? ''" :error="$errors->get('title')" />
      </div>

      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <div x-effect="document.getElementById('provenance-precision-help').textContent = help[precision]">
          <x-select id="provenance-precision" name="date_precision" :label="__('Date precision')" :help="collect($precisions)->firstWhere('value', $precision)['help'] ?? ''" :error="$errors->get('date_precision')" x-model="precision" @change="date = ''">
            @foreach ($precisions as $option)
              <option value="{{ $option['value'] }}" @selected($precision === $option['value'])>{{ $option['label'] }}</option>
            @endforeach
          </x-select>
        </div>

        <fieldset x-show="precision === 'day'" :disabled="precision !== 'day'" @disabled($precision !== 'day') class="min-w-0">
          <x-input type="date" id="provenance-date-day" name="occurred_on" :label="__('Date')" optional :value="$precision === 'day' ? $value('occurred_on') : ''" x-model="date" :error="$errors->get('occurred_on')" />
        </fieldset>
        <fieldset x-show="precision === 'month'" x-cloak :disabled="precision !== 'month'" @disabled($precision !== 'month') class="min-w-0">
          <x-input type="month" id="provenance-date-month" name="occurred_on" :label="__('Month and year')" optional :value="$precision === 'month' ? $value('occurred_on') : ''" placeholder="1962-03" x-model="date" :error="$errors->get('occurred_on')" />
        </fieldset>
        <fieldset x-show="precision === 'year'" x-cloak :disabled="precision !== 'year'" @disabled($precision !== 'year') class="min-w-0">
          <x-input type="number" min="1" max="9999" step="1" id="provenance-date-year" name="occurred_on" :label="__('Year')" optional :value="$precision === 'year' ? $value('occurred_on') : ''" placeholder="1975" x-model="date" :error="$errors->get('occurred_on')" />
        </fieldset>
        <fieldset x-show="precision === 'circa'" x-cloak :disabled="precision !== 'circa'" @disabled($precision !== 'circa') class="min-w-0">
          <x-input type="number" min="1" max="9999" step="1" id="provenance-date-circa" name="occurred_on" :label="__('Approximate year')" optional :value="$precision === 'circa' ? $value('occurred_on') : ''" placeholder="1975" x-model="date" :error="$errors->get('occurred_on')" />
        </fieldset>
      </div>

      <div class="grid grid-cols-[repeat(auto-fit,minmax(11rem,1fr))] gap-4">
        <x-input id="provenance-from" name="from" :label="__('From')" optional :value="$value('from')" :placeholder="__('A person, a shop, an estate')" :error="$errors->get('from')" />
        <x-input id="provenance-to" name="to" :label="__('To')" optional :value="$value('to')" :placeholder="__('Who held it afterwards')" :error="$errors->get('to')" />
        <x-input id="provenance-location" name="location" :label="__('Location')" optional :value="$value('location')" :placeholder="__('Scranton')" :error="$errors->get('location')" />
      </div>

      <x-textarea id="provenance-description" name="description" :label="__('Description')" optional :rows="3" :value="$value('description')" :placeholder="__('What happened, and how it is known')" :error="$errors->get('description')" />

      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <x-input id="provenance-reference" name="reference_number" :label="__('Reference number')" optional :value="$value('reference_number')" :placeholder="__('Lot 118')" :error="$errors->get('reference_number')" />
        <x-input type="url" id="provenance-source" name="source_url" :label="__('Source link')" optional :value="$value('source_url')" :placeholder="__('Where this event can be checked')" :error="$errors->get('source_url')" />
      </div>

      <x-select id="provenance-transaction" name="transaction" :label="__('Linked transaction')" optional :help="__('What the moment cost lives on the transaction, so it is not typed again here.')" :error="array_merge($errors->get('transaction'), $errors->get('possession_transaction_id'))">
        <option value="">{{ __('Not linked to a transaction') }}</option>
        @foreach ($viewModel->transactionOptions() as $option)
          <option value="{{ $option['value'] }}" @selected($transaction === $option['value'])>{{ $option['label'] }}</option>
        @endforeach
      </x-select>

      <div class="flex flex-col gap-1">
        <x-checkbox id="provenance-verified" name="is_verified" :checked="$isVerified" aria-describedby="provenance-verified-help">
          {{ __('This event has been verified') }}
        </x-checkbox>
        <p id="provenance-verified-help" class="ps-6.5 text-xs text-muted">{{ __('Tick it when a document, a record or an expert confirms it. Family memory alone stays unverified.') }}</p>
      </div>
    </div>

    <div class="flex flex-wrap gap-2 border-t border-hairline bg-card px-4 py-3">
      <x-button>{{ $form['isEditing'] ? __('Save changes') : __('Add event') }}</x-button>
      @if ($form['cancelUrl'] !== null)
        <x-button.secondary :href="$form['cancelUrl']">{{ __('Cancel') }}</x-button.secondary>
      @else
        <x-button.secondary type="button" @click="adding = false">{{ __('Cancel') }}</x-button.secondary>
      @endif
    </div>
  </div>
</x-form>
