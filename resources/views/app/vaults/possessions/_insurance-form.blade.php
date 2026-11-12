{{-- The form recording an insurance record of a possession: one policy period. --}}
{{-- @var \App\ViewModels\Vaults\PossessionInsuranceViewModel $viewModel --}}
@php
  $form = $viewModel->form();
  $value = fn (string $key, string $default = ''): string => (string) old($key, $form['values'][$key] ?? $default);
  $currency = \App\Enums\CurrencyEnum::tryFrom((string) old('currency')) ?? $form['currency'];
  $status = $value('status', 'active');
  $isListedIndividually = session()->hasOldInput() ? (bool) old('is_listed_individually') : $form['isListedIndividually'];
@endphp

<x-form
  :method="$form['method']"
  :action="$form['action']"
  class="overflow-hidden rounded-lg border border-[color-mix(in_srgb,#9a3f2b_45%,var(--hairline))] bg-canvas"
  aria-labelledby="insurance-form-title"
>
  <div class="flex min-h-12 items-center gap-3 border-b border-[color-mix(in_srgb,#9a3f2b_30%,var(--hairline))] bg-[color-mix(in_srgb,#9a3f2b_9%,var(--canvas))] px-4 py-3.5">
    <span class="flex size-7 shrink-0 items-center justify-center rounded-md border border-[color-mix(in_srgb,#9a3f2b_35%,var(--hairline))] bg-canvas text-[#8a3826] dark:text-[#dd7f66]" aria-hidden="true">
      <x-icon :name="$form['isEditing'] ? 'note' : 'plus'" :size="15" />
    </span>
    <div class="min-w-0 flex-1">
      <h3 id="insurance-form-title" class="text-card-title text-[#8a3826] dark:text-[#dd7f66]">{{ $form['isEditing'] ? __('Edit insurance record') : __('New insurance record') }}</h3>
      <p class="text-xs text-muted">{{ $form['isEditing'] ? __('Changes apply to this record only. Earlier records are not touched.') : __('Recorded on :name', ['name' => $viewModel->page()->name()]) }}</p>
    </div>
  </div>

  <div class="flex flex-col gap-4.5 p-4">
    <div class="grid grid-cols-[repeat(auto-fit,minmax(15rem,1fr))] items-start gap-4">
      <x-input id="ins-insurer" name="insurer" :label="__('Insurer')" required :value="$value('insurer')" :placeholder="__('Sabre Insurance, Scranton Mutual')" :error="$errors->get('insurer')" />

      <fieldset class="flex flex-col gap-1.5">
        <legend class="mb-1.5 text-label text-ink">{{ __('Status') }}</legend>
        <div class="flex min-h-9 flex-wrap items-center gap-1.5">
          @foreach ($viewModel->statuses() as $option)
            <label class="inline-flex h-8 cursor-pointer items-center rounded-full border border-hairline bg-canvas px-3.25 text-sm font-medium whitespace-nowrap text-body transition duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink has-checked:border-primary has-checked:bg-primary has-checked:text-on-primary has-focus-visible:ring-[3px] has-focus-visible:ring-focus-ring/45">
              <input type="radio" name="status" value="{{ $option['value'] }}" class="sr-only" @checked($status === $option['value']) />
              {{ $option['label'] }}
            </label>
          @endforeach
        </div>
        <x-error :messages="$errors->get('status')" />
      </fieldset>
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
      <x-input id="ins-policy" name="policy_number" :label="__('Policy number')" optional :value="$value('policy_number')" :error="$errors->get('policy_number')" />
      <x-input id="ins-coverage" name="coverage" :label="__('Coverage')" optional :value="$value('coverage')" :placeholder="__('Theft, fire and accidental damage')" :error="$errors->get('coverage')" />
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(9.375rem,1fr))] gap-4">
      <x-input type="number" min="0" step="0.01" id="ins-value" name="insured_value" :label="__('Insured value')" required :value="$value('insured_value')" :error="$errors->get('insured_value')" />
      <x-currency-picker id="ins-currency" name="currency" :label="__('Currency')" :value="$currency" :error="$errors->get('currency')" />
      <x-input type="number" min="0" step="0.01" id="ins-excess" name="excess" :label="__('Excess')" optional :value="$value('excess')" :error="$errors->get('excess')" />
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(12.5rem,1fr))] gap-4">
      <x-input type="date" id="ins-start" name="starts_on" :label="__('Starts')" optional :value="$value('starts_on')" :error="$errors->get('starts_on')" />
      <x-input type="date" id="ins-end" name="ends_on" :label="__('Ends or renews')" optional :help="__('Leave empty for a policy with no end date.')" :value="$value('ends_on')" :error="$errors->get('ends_on')" />
    </div>

    <div class="rounded-md border border-hairline px-3.5 py-3">
      <x-checkbox id="ins-listed" name="is_listed_individually" :checked="$isListedIndividually">
        <span class="flex flex-col gap-0.5">
          <span class="text-sm font-semibold text-ink">{{ __('Listed individually on the policy') }}</span>
          <span class="text-sm text-pretty text-muted">{{ __('The policy names this possession, rather than covering it under the general contents of the home.') }}</span>
        </span>
      </x-checkbox>
    </div>

    <fieldset class="flex flex-col gap-2.5">
      <legend class="mb-2.5"><x-caption>{{ __('Who to call at the insurer') }}</x-caption></legend>
      <div class="grid grid-cols-[repeat(auto-fit,minmax(11.25rem,1fr))] gap-4">
        <x-input id="ins-contact" name="contact_name" :label="__('Name')" optional :value="$value('contact_name')" :error="$errors->get('contact_name')" />
        <x-input type="email" id="ins-email" name="contact_email" :label="__('Email')" optional :value="$value('contact_email')" :error="$errors->get('contact_email')" />
        <x-input type="tel" id="ins-phone" name="contact_phone" :label="__('Phone')" optional :value="$value('contact_phone')" :error="$errors->get('contact_phone')" />
      </div>
    </fieldset>

    <x-textarea id="ins-note" name="note" :label="__('Note')" optional :rows="3" :value="$value('note')" :placeholder="__('Conditions, exclusions, what the insurer asked for')" :error="$errors->get('note')" />
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
