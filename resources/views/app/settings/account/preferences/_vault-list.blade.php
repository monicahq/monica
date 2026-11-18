{{-- Whether signing in shows the list of vaults, or opens the default vault straight away. --}}
{{-- @var bool $showsVaultList --}}
<x-page-section :title="__('Vaults')">
  <x-slot:help>
    <x-help
      :label="__('Help about the list of vaults')"
      :title="__('Why would I turn this off?')"
      guide="account.preferences#the-list-of-vaults"
    >{{ __('If you keep everything in one vault, the list holds a single card you click every time. While it is off, your default vault cannot be deleted.') }}</x-help>
  </x-slot:help>

  <x-panel :flush="true">
    <x-row :title="__('Show the list of vaults')" :description="__('When this is off, you go straight to your default vault.')" icon="vault">
      <x-slot:end>
        <x-form
          method="put"
          :action="route('settings.vaultList.update')"
          id="vault-list-form"
          x-target="vault-list-form"
          class="dim-when-busy"
        >
          <input type="hidden" name="shows_vault_list" value="0" />

          <label for="shows-vault-list" class="flex cursor-pointer items-center">
            <x-switch
              id="shows-vault-list"
              name="shows_vault_list"
              :checked="$showsVaultList"
              :aria-label="__('Show the list of vaults')"
              x-on:change="$el.form.requestSubmit()"
            />
          </label>
        </x-form>
      </x-slot:end>
    </x-row>
  </x-panel>
</x-page-section>
