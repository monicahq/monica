{{-- The form recording a loan of a possession, editing one, or marking one as returned. --}}
{{-- @var \App\ViewModels\Vaults\PossessionLoansViewModel $viewModel --}}
@php
  $form = $viewModel->form();
  $value = fn (string $key, string $default = ''): string => (string) old($key, $form['values'][$key] ?? $default);
  $conditions = $viewModel->conditions();
  $currency = \App\Enums\CurrencyEnum::tryFrom((string) old('deposit_currency')) ?? $form['currency'];
  $isCreatingContact = (bool) old('new_contact', false);
  $isInProvenance = (bool) old('is_in_provenance', $value('is_in_provenance') === '1');
@endphp

<x-form
  :method="$form['method']"
  :action="$form['action']"
  class="overflow-hidden rounded-lg border border-hairline bg-canvas"
  aria-labelledby="loan-form-title"
>
  <div
    x-data="{
      direction: @js($value('direction')),
      status: @js($value('status')),
      due: @js($value('due_on')),
      provenance: @js($isInProvenance),
      contactName: @js($form['contactName']),
      creating: @js($isCreatingContact),
      canCreateContact: @js(! $form['isEditing']),
      firstName: @js($value('new_contact_first_name')),
      lastName: @js($value('new_contact_last_name')),
      wordings: @js($viewModel->summaryWordings()),
      startNewContact(query) {
        const parts = query.trim().split(/\s+/).filter(Boolean)
        this.firstName = parts[0] ?? ''
        this.lastName = parts.slice(1).join(' ')
        this.creating = true
        this.$nextTick(() => document.getElementById('loan-first-name')?.focus())
      },
      get summary() {
        const name = this.creating ? ([this.firstName.trim(), this.lastName.trim()].filter(Boolean).join(' ') || this.wordings.newContact) : this.contactName
        if (! name) return this.wordings.empty
        const key = this.direction + this.status.charAt(0).toUpperCase() + this.status.slice(1)
        let line = this.wordings[key].replace(':name', name)
        if (this.status !== 'returned') line += ' ' + (this.due ? this.wordings.due : this.wordings.noDue)
        if (this.provenance && this.status !== 'planned') line += ' ' + this.wordings.provenance
        return line
      },
    }"
    x-on:record-chosen="contactName = $event.detail.name"
    class="contents"
  >
    <div class="flex min-h-12 items-center gap-3 border-b border-hairline bg-card px-4 py-3.5">
      <span class="flex size-7 shrink-0 items-center justify-center rounded-md border border-hairline bg-canvas text-[#7a3d6b] dark:text-[#d08bbf]" aria-hidden="true">
        <x-icon :name="$form['isEditing'] ? 'note' : 'relationship'" :size="15" />
      </span>
      <h3 id="loan-form-title" class="text-card-title text-ink">{{ $form['title'] }}</h3>
    </div>

    <div class="flex flex-col gap-4.5 p-4">
      <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
        <x-select id="loan-direction" name="direction" :label="__('Direction')" :error="$errors->get('direction')" x-model="direction">
          @foreach ($viewModel->directions() as $option)
            <option value="{{ $option['value'] }}" @selected($value('direction') === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>

        <x-select id="loan-status" name="status" :label="__('Status')" :error="$errors->get('status')" x-model="status">
          @foreach ($viewModel->statuses() as $option)
            <option value="{{ $option['value'] }}" @selected($value('status') === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>
      </div>

      <div x-show="! creating">
        <x-record-picker
          id="loan-contact"
          name="contact_id"
          :url="$viewModel->candidatesUrl()"
          :label="__('Contact')"
          :chosen="$value('contact_id')"
          :chosen-name="$form['contactName']"
          :error="$errors->get('contact_id')"
        />
      </div>

      @unless ($form['isEditing'])
        <fieldset x-show="creating" x-cloak :disabled="! creating" class="flex flex-col gap-3 rounded-md border border-hairline p-3">
          <legend class="sr-only">{{ __('New contact') }}</legend>
          <input type="hidden" name="new_contact" value="1">
          <div class="flex flex-wrap items-center gap-2">
            <x-badge tone="info">{{ __('New contact') }}</x-badge>
            <button type="button" class="focus-ring ms-auto min-h-6 rounded-xs py-1 text-xs text-ink underline" @click="creating = false; $nextTick(() => document.getElementById('loan-contact')?.focus())">{{ __('Search your contacts instead') }}</button>
          </div>
          <div class="grid grid-cols-[repeat(auto-fit,minmax(11rem,1fr))] gap-4">
            <x-input id="loan-first-name" name="new_contact_first_name" :label="__('First name')" x-model.lazy="firstName" :error="$errors->get('new_contact_first_name')" />
            <x-input id="loan-last-name" name="new_contact_last_name" :label="__('Last name')" optional x-model.lazy="lastName" :error="$errors->get('new_contact_last_name')" />
          </div>
          <p class="text-xs text-pretty text-muted">{{ __('Added to the people of this vault when you save the loan. The rest of their page can be filled in later.') }}</p>
        </fieldset>
      @endunless

      <div class="grid grid-cols-[repeat(auto-fit,minmax(11rem,1fr))] gap-4">
        <x-input type="date" id="loan-started" name="started_on" :label="__('Started on')" :value="$value('started_on')" :error="$errors->get('started_on')" />
        <x-input type="date" id="loan-due" name="due_on" :label="__('Due back')" optional :value="$value('due_on')" x-model="due" :error="$errors->get('due_on')" />
        <x-select id="loan-condition-out" name="condition_out" :label="__('Condition when it left')" optional :error="$errors->get('condition_out')">
          <option value="">{{ __('Not recorded') }}</option>
          @foreach ($conditions as $option)
            <option value="{{ $option['value'] }}" @selected($value('condition_out') === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>
      </div>

      <fieldset x-show="status === 'returned'" x-cloak :disabled="status !== 'returned'" class="grid grid-cols-[repeat(auto-fit,minmax(11rem,1fr))] gap-4">
        <legend class="sr-only">{{ __('Return') }}</legend>
        <x-input type="date" id="loan-returned" name="returned_on" :label="__('Returned on')" :value="$value('returned_on')" :error="$errors->get('returned_on')" />
        <x-select id="loan-condition-back" name="condition_back" :label="__('Condition when it came back')" optional :error="$errors->get('condition_back')">
          <option value="">{{ __('Not recorded') }}</option>
          @foreach ($conditions as $option)
            <option value="{{ $option['value'] }}" @selected($value('condition_back') === $option['value'])>{{ $option['label'] }}</option>
          @endforeach
        </x-select>
      </fieldset>

      <div class="grid grid-cols-[repeat(auto-fit,minmax(9.375rem,1fr))] gap-4">
        <x-input type="number" min="0" step="0.01" id="loan-deposit" name="deposit_amount" :label="__('Deposit')" optional :value="$value('deposit_amount')" :error="$errors->get('deposit_amount')" />
        <x-currency-picker id="loan-deposit-currency" name="deposit_currency" :label="__('Currency')" optional :value="$currency" :error="$errors->get('deposit_currency')" />
      </div>

      <x-textarea id="loan-purpose" name="purpose" :label="__('Purpose')" optional :rows="2" :value="$value('purpose')" :placeholder="__('An exhibition, a repair off site, a loan to a friend')" :error="$errors->get('purpose')" />

      <div class="flex flex-col gap-1">
        <x-checkbox id="loan-provenance" name="is_in_provenance" :checked="$isInProvenance" x-model="provenance" aria-describedby="loan-provenance-help">
          {{ __('Add to the provenance') }}
        </x-checkbox>
        <p id="loan-provenance-help" class="ps-6.5 text-xs text-pretty text-muted">{{ __('Loans to an institution and exhibitions belong to the history of the possession. This records a change of custody when the loan starts and another when it ends, and deleting the loan deletes them. Personal loans can be left out.') }}</p>
      </div>

      <p class="text-xs text-pretty text-muted" aria-live="polite" x-text="summary">{{ $viewModel->summaryWordings()['empty'] }}</p>
    </div>

    <div class="flex flex-wrap gap-2 border-t border-hairline bg-card px-4 py-3">
      <x-button>{{ $form['submitLabel'] }}</x-button>
      @if ($form['cancelUrl'] !== null)
        <x-button.secondary :href="$form['cancelUrl']">{{ __('Cancel') }}</x-button.secondary>
      @else
        <x-button.secondary type="button" @click="lending = false; $nextTick(() => document.getElementById('loan-add-button')?.focus())">{{ __('Cancel') }}</x-button.secondary>
      @endif
    </div>
  </div>
</x-form>
