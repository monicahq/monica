{{-- What a new possession would cost to replace today, and the currency of that value. --}}
{{-- @var \App\ViewModels\Vaults\PossessionFormViewModel $viewModel --}}
<x-page-section :title="__('What it is worth')" :description="__('Useful for insurance. How you got it is recorded in its transactions, once it is added.')">
  <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
    <x-input
      type="number"
      id="declared_value"
      :label="__('Declared value')"
      :value="$viewModel->declaredValue()"
      :error="$errors->get('declared_value')"
      :help="__('What it would cost to replace today.')"
      min="0"
      step="0.01"
      placeholder="0"
      optional
    />

    <x-currency-picker id="declared_value_currency" :label="__('Currency')" :value="$viewModel->currency()" :error="$errors->get('declared_value_currency')" />
  </div>
</x-page-section>
