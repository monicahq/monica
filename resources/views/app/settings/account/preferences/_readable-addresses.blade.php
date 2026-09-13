{{-- Whether a link names the vault and the person it opens, or carries their identifier instead. --}}
{{--
  @var \App\ViewModels\Settings\Account\Preferences\PreferencesViewModel $viewModel
  @var string $rows
--}}
<x-row :title="__('Links')" :description="__('What a link to a vault or a person is written with. Turning names off keeps the ones you have already saved working.')" icon="info">
  <x-slot:end>
    <x-form
      method="put"
      :action="route('settings.preferences.update')"
      id="readable-addresses-form"
      x-target="{{ $rows }}"
      class="transition-opacity [&[aria-busy]]:opacity-60"
    >
      @include('app.settings.account.preferences._carried-values', ['owns' => 'uses_readable_addresses'])

      <x-dropdown
        name="uses_readable_addresses"
        :label="$viewModel->addressStyleLabel()"
        :options="$viewModel->addressStyles()"
        :title="__('Links')"
        monospaced-hints
      />
    </x-form>
  </x-slot:end>
</x-row>
