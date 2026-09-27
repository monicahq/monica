{{-- The assistants somebody let in through OAuth, with the way to disconnect each one. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<div id="connected-assistants" x-merge="replace" class="dim-when-busy">
  @forelse ($viewModel->connectedAssistants() as $assistant)
    <x-row
      :title="$assistant['name']"
      :description="__('Connected :connected', ['connected' => $assistant['connectedAt']])"
      icon="link"
    >
      <x-slot:end>
        <div class="flex flex-wrap items-center justify-end gap-2.5" x-data="{ confirming: false }">
          <x-button.secondary size="small" type="button" x-show="! confirming" @click="confirming = true">{{ __('Disconnect') }}</x-button.secondary>

          <div x-cloak x-show="confirming" class="flex flex-wrap items-center justify-end gap-2.5">
            <p class="text-xs text-error">{{ __('It loses access at once, and has to ask you again to come back.') }}</p>

            <x-button.secondary size="small" type="button" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>

            <x-form method="delete" :action="route('settings.connectedAssistants.destroy', $assistant['clientId'])" x-target="connected-assistants">
              <x-button.danger size="small" type="submit" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Disconnect') }}</x-button.danger>
            </x-form>
          </div>
        </div>
      </x-slot:end>
    </x-row>
  @empty
    <x-empty-state :title="__('No assistant connected')">
      <x-slot:icon>
        <x-icon name="link" :size="30" :stroke-width="1.4" />
      </x-slot:icon>

      {{ __('Point an assistant at the MCP server of this instance and it asks you here before it can reach anything.') }}
    </x-empty-state>
  @endforelse
</div>
