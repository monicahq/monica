{{--
  The settings of one vault. Three things for now: what it is called, what a
  contact written down here starts from, and the way to be rid of the vault.

  The template section only asks a question when there is one to ask. An account
  with a single contact template has nothing to choose between, so the screen
  names what the vault uses and points at where more templates are made.

  @var \App\ViewModels\Vaults\VaultSettingsViewModel $viewModel
--}}
@php
  $vault = $viewModel->vault();
  $values = $viewModel->values();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[['label' => $vault->name, 'url' => route('vaults.show', $vault)], ['label' => __('Settings')]]" />
  </x-slot:breadcrumb>

  {{--
    Nothing in the dock is current. The settings of a vault are not one of the
    four destinations: you get here from the menu behind the chip, and marking
    one of the four would claim otherwise.
  --}}
  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="vault-settings" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

  {{--
    The wording of the vault. The preview beside the swatches follows what is
    being typed rather than what has been saved, so it is held in the browser;
    the colour travels in a hidden field the swatches write to, since a row of
    buttons is not a field.
  --}}
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
        :subtitle="__('The name is what you see in the dock and on the list. The description is for the version of you who comes back in a year.')"
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
            :placeholder="__('Everyone I am related to, and the people who come with them.')"
            :help="__('One or two sentences.')"
            :error="$errors->get('description')"
          />

          {{--
            The swatches are buttons rather than radios, so what says what they
            are is a group with a name of its own: a label pointing at the hidden
            field the buttons write to would point at nothing anybody can see.
          --}}
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
                    :class="colour === @js($option['value']) ? 'border-ink shadow-[inset_0_0_0_2px_var(--canvas)]' : 'border-transparent shadow-[0_1px_2px_rgb(29_29_32_/_0.12)]'"
                    style="background: {{ $option['value'] }}"
                    class="size-8.5 cursor-pointer rounded-[10px] border-2 transition-transform duration-(--duration-fast) ease-out hover:-translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
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
    :subtitle="__('Every contact added to this vault begins with this template. The ones already here keep the version they were written on.')"
    icon="people"
  >
    {{--
      Every version of every template, offered even when the account has one of
      each: a select showing the one thing on offer says what the vault does,
      which a sentence about there being no choice does not.

      The versions sit under the template they belong to, because "Version 2" on
      its own is only half an answer.
    --}}
    @if ($viewModel->templates() !== [])
      <x-form method="put" :action="$viewModel->defaultTemplateAction()" class="flex flex-col gap-4">
        <x-select
          id="template_version_id"
          :label="__('Default template')"
          :help="__('An older version is on offer too, so this vault can start its contacts on the shape you want rather than the newest one.')"
          :error="$errors->get('template_version_id')"
          class="max-w-105"
        >
          @foreach ($viewModel->templates() as $template)
            <optgroup label="{{ $template['name'] }}">
              @foreach ($template['versions'] as $version)
                <option value="{{ $version['id'] }}" @selected($viewModel->currentTemplateVersionId() === (string) $version['id'])>{{ $version['label'] }}</option>
              @endforeach
            </optgroup>
          @endforeach
        </x-select>

        <x-button class="self-start">{{ __('Save') }}</x-button>
      </x-form>
    @else
      <div class="flex flex-col gap-3">
        @if ($viewModel->currentTemplateName())
          <p class="max-w-[60ch] text-sm text-pretty text-body">
            {{ __('Contacts here start from :name.', ['name' => $viewModel->currentTemplateName()]) }}
          </p>
        @else
          <p class="max-w-[60ch] text-sm text-pretty text-body">
            {{ __('Nothing is set, so whoever writes a contact down here picks a template themselves.') }}
          </p>
        @endif

        <p class="max-w-[60ch] text-sm text-pretty text-muted">
          {{ __('Your account has no published contact template to choose from.') }}
          <x-link :href="$viewModel->templatesUrl()" turbo>{{ __('Your templates') }}</x-link>
        </p>
      </div>
    @endif
  </x-panel>

  {{--
    The destructive half. It asks twice: the button raises the card, and the
    card says what the vault holds before it goes.
  --}}
  <x-panel :title="__('Delete this vault')" icon="vault" tone="danger">
    <div class="flex flex-wrap items-center gap-3.5">
      <div class="flex max-w-[60ch] flex-col gap-2 text-sm text-pretty text-muted">
        <p>{{ __('Everything filed in this vault goes with it: the contacts, and everything written about them.') }}</p>

        <p>{{ __('Nothing in the application brings a vault back, so this is the end of it.') }}</p>
      </div>

      <x-modal.warning
        :title="__('Delete “:name”?', ['name' => $vault->name])"
        :confirm="__('Delete the vault')"
        :cancel="__('Keep it')"
        :action="$viewModel->destroyAction()"
      >
        <x-slot:trigger>
          <x-button.danger type="button" size="small" @click="open = true" class="sm:ml-auto">
            {{ __('Delete this vault') }}
          </x-button.danger>
        </x-slot:trigger>

        @if ($viewModel->deletionConsequence() !== '')
          <p>{{ $viewModel->deletionConsequence() }}</p>
        @endif

        {{--
          The last vault of an account is always the default one, so the two
          sentences never both apply: either something else takes the title
          on, or there is nothing left to take it.
        --}}
        @if ($viewModel->isTheLastVault())
          <p>{{ __('This is the only vault you have, so your account is left holding nothing and you go back to the start.') }}</p>

          <p>{{ __('The next vault you make becomes the one things land in.') }}</p>
        @elseif ($vault->is_default)
          <p>{{ __('This is your default vault, so another one takes over as the place things land.') }}</p>
        @endif

        <p>{{ __('This cannot be undone.') }}</p>
      </x-modal.warning>
    </div>
  </x-panel>
</x-shell>
