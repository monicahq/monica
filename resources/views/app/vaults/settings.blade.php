{{-- The settings of one vault. --}}
{{-- @var \App\ViewModels\Vaults\VaultSettingsViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $values = $viewModel->values();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('Settings')]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="vault-settings" />
  </x-slot:sidebar>

  <x-modal.confirm-password
    :offers-password="$viewModel->credentials()->offersPassword()"
    :offers-passkey="$viewModel->credentials()->offersPasskey()"
    :description="__('An export holds everything in the vault, sensitive fields included, so we ask for a credential first. You will not be asked again for a little while.')"
  />

  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

  <div
    x-data="{
      name: @js($values['name']),
      colour: @js($values['colour']),
      get initial() { return (this.name.trim()[0] || @js(__('V'))).toUpperCase() },
      get previewName() { return this.name.trim() || @js(__('Untitled vault')) },
      get named() { return this.name.trim() !== '' },
    }"
  >
    <x-form method="put" :action="$viewModel->updateAction()">
      <x-panel
        :title="__('What this vault is called')"
          icon="vault"
      >
        <div class="flex flex-col gap-4.5">
          <x-input
            id="name"
            x-model="name"
            :value="$values['name']"
            :label="__('Name')"
            :placeholder="__('Famille')"
            :error="$errors->get('name')"
            maxlength="255"
            required
          />

          <x-textarea
            id="description"
            :label="__('Description')"
            optional
            :value="$values['description']"
            :placeholder="__('Everybody I am related to, and the people who come with them.')"
            :help="__('One or two sentences.')"
            :error="$errors->get('description')"
          />

          <div class="flex flex-col gap-2">
            <span class="text-label text-ink">{{ __('Colour') }}</span>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-4">
              <input type="hidden" name="colour" value="{{ $values['colour'] }}" :value="colour" />

              <div class="flex flex-wrap gap-2.5" role="group" aria-label="{{ __('Colour') }}">
                @foreach ($viewModel->colours() as $option)
                  <button
                    type="button"
                    aria-label="{{ $option['label'] }}"
                    :aria-pressed="colour === @js($option['value']) ? 'true' : 'false'"
                    @click="colour = @js($option['value'])"
                    :class="colour === @js($option['value']) ? 'border-ink inset-ring-2 inset-ring-canvas' : 'border-transparent shadow-control'"
                    style="background: {{ $option['value'] }}"
                    class="size-8.5 cursor-pointer rounded-[10px] border-2 transition-transform duration-(--duration-fast) ease-out hover:-translate-y-px focus-ring"
                  ></button>
                @endforeach
              </div>

              <div class="flex items-center gap-3 border-hairline-soft sm:ml-auto sm:border-l sm:pl-5">
                <span
                  :style="`background: ${colour}`"
                  class="flex size-8.5 items-center justify-center rounded-md text-sm font-semibold text-white"
                  aria-hidden="true"
                  x-text="initial"
                ></span>

                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-ink" x-text="previewName"></p>

                  <p class="truncate font-mono text-xs text-muted-soft" x-text="colour"></p>
                </div>
              </div>
            </div>

            <x-error :messages="$errors->get('colour')" />
          </div>
        </div>

        <x-slot:footer>
          <div class="flex justify-end">
            <x-button size="small" ::disabled="! named">{{ __('Save changes') }}</x-button>
          </div>
        </x-slot:footer>
      </x-panel>
    </x-form>
  </div>

  <x-panel
    :title="__('What a new contact starts from')"
    icon="people"
  >
    @if ($viewModel->templates() !== [])
      <x-form id="default-template-form" method="put" :action="$viewModel->defaultTemplateAction()">
        <x-select
          id="structure_version_id"
          :label="__('Default template')"
          :error="$errors->get('structure_version_id')"
          class="max-w-105"
        >
          @foreach ($viewModel->templates() as $template)
            <optgroup label="{{ $template['name'] }}">
              @foreach ($template['versions'] as $version)
                <option value="{{ $version['id'] }}" @selected($viewModel->currentStructureVersionId() === (string) $version['id'])>{{ $version['label'] }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </x-select>
      </x-form>
    @else
      <div class="flex flex-col gap-3">
        @if ($viewModel->currentTemplateName())
          <p class="max-w-prose text-sm text-pretty text-body">
            {{ __('Contacts here start from :name.', ['name' => $viewModel->currentTemplateName()]) }}
          </p>
        @else
          <p class="max-w-prose text-sm text-pretty text-body">
            {{ __('Nothing is set, so whoever writes a contact down here picks a template themselves.') }}
          </p>
        @endif

        <p class="max-w-prose text-sm text-pretty text-muted">
          {{ __('Your account has no published contact template to choose from.') }}
          <x-link :href="$viewModel->templatesUrl()" turbo>{{ __('Your templates') }}</x-link>
        </p>
      </div>
    @endif

    @if ($viewModel->templates() !== [])
      <x-slot:footer>
        <div class="flex justify-end">
          <x-button size="small" form="default-template-form">{{ __('Save changes') }}</x-button>
        </div>
      </x-slot:footer>
    @endif
  </x-panel>

  <x-panel
    :title="__('Export this vault')"
    icon="file"
  >
    <x-form id="vault-export-form" method="post" :action="$viewModel->exportAction()" class="flex flex-col gap-4">
      <input type="hidden" name="vault" value="{{ $vault->id }}" />

      <p class="max-w-prose text-sm text-pretty text-body">{{ __('One file holding the contacts, entities, relationships, life events, reminders and files of this vault, with the templates they use. It can be downloaded for seven days from Export and import.') }}</p>

      <x-notice tone="warning" :title="__('The file is not encrypted')">
        {{ __('It holds everything in this vault, sensitive fields included. Anybody who gets hold of it can read all of it, so keep it somewhere only you can reach.') }}
      </x-notice>
    </x-form>

    <x-slot:footer>
      <div class="flex flex-wrap items-center justify-end gap-3.5">
        @if ($viewModel->exportRefusal() !== null)
          <p id="vault-export-refusal" class="max-w-reading-narrow text-xs text-pretty text-muted">{{ $viewModel->exportRefusal() }}</p>

          <x-button size="small" type="button" aria-disabled="true" aria-describedby="vault-export-refusal">{{ __('Prepare the export') }}</x-button>
        @else
          <x-button size="small" form="vault-export-form" @click.prevent="$dispatch('confirm-password', { form: $el.form })">{{ __('Prepare the export') }}</x-button>
        @endif
      </div>
    </x-slot:footer>
  </x-panel>

  <x-delete-section :title="__('Delete this vault')" icon="vault">
    <p>{{ __('Everything filed in this vault is deleted with it: the contacts, and everything written about them.') }}</p>

    <p>{{ __('Nothing in the application restores a vault, so this cannot be undone.') }}</p>

    @if ($viewModel->deletionRefusal() !== null)
      <p class="font-medium">
        <span id="vault-delete-refusal">{{ $viewModel->deletionRefusal() }}</span>
        <x-link :href="$viewModel->preferencesUrl()">{{ __('Open Preferences') }}</x-link>
      </p>
    @endif

    <x-error :messages="$errors->get('vault')" />

    <x-slot:trigger>
      @if ($viewModel->deletionRefusal() !== null)
        <x-button.danger type="button" aria-disabled="true" aria-describedby="vault-delete-refusal">
          {{ __('Delete this vault') }}
        </x-button.danger>
      @else
        <x-modal.warning
          :title="__('Delete “:name”?', ['name' => $vault->name])"
          :confirm="__('Delete the vault')"
          :cancel="__('Keep it')"
          :action="$viewModel->destroyAction()"
        >
          <x-slot:trigger>
            <x-button.danger type="button" @click="open = true">
              {{ __('Delete this vault') }}
            </x-button.danger>
          </x-slot:trigger>

          @if ($viewModel->deletionConsequence() !== '')
            <p>{{ $viewModel->deletionConsequence() }}</p>
          @endif

          @if ($viewModel->isTheLastVault())
            <p>{{ __('This is the only vault you have. Your account will hold nothing, and you start again from the beginning.') }}</p>

            <p>{{ __('The next vault you make becomes the one new records go to.') }}</p>
          @elseif ($vault->is_default)
            <p>{{ __('This is your default vault, so another one becomes the vault new records go to.') }}</p>
          @endif

          <p>{{ __('This cannot be undone.') }}</p>
        </x-modal.warning>
      @endif
    </x-slot:trigger>
  </x-delete-section>
</x-shell>
