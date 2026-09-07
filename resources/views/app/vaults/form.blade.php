{{-- The form that opens a new compartment in the account. --}}
{{-- @var \App\ViewModels\Vaults\VaultFormViewModel $viewModel --}}
@php
  $values = $viewModel->values();
@endphp
<x-account-layout :title="$viewModel->title()">
  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" class="max-w-140" />

  <div
    x-data="{
      name: @js($values['name']),
      colour: @js($values['colour']),
      isDefault: @js($values['isDefault']),
      get initial() { return (this.name.trim()[0] || @js(__('V'))).toUpperCase() },
      get previewName() { return this.name.trim() || @js(__('Untitled vault')) },
      get named() { return this.name.trim() !== '' },
    }"
    class="space-y-8.5"
  >
    <x-form id="vault-form" method="post" :action="$viewModel->action()" class="space-y-8.5">
      <x-box :title="__('What it is for')" :description="__('The name is what you see in the switcher and on the vault list. The description says what the vault holds.')">
        <div class="space-y-4">
          <x-input
            id="name"
            x-model="name"
            :value="$values['name']"
            :label="__('Name')"
            :placeholder="__('Famille')"
            :help="__('Short. “Famille”, “Pro”, “Amis”.')"
            :error="$errors->get('name')"
            maxlength="255"
            autofocus
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
        </div>
      </x-box>

      <x-box :title="__('Colour')" :description="__('Used to tell vaults apart at a glance, in the switcher and on the list. It carries no meaning of its own.')">
        <div class="flex flex-wrap items-center gap-x-6 gap-y-4">
          <input type="hidden" name="colour" value="{{ $values['colour'] }}" :value="colour" />

          <div class="flex flex-wrap gap-2.5">
            @foreach ($viewModel->colours() as $option)
              <button
                type="button"
                aria-label="{{ $option['label'] }}"
                :aria-pressed="colour === @js($option['value']) ? 'true' : 'false'"
                @click="colour = @js($option['value'])"
                :class="colour === @js($option['value']) ? 'ring-2 ring-focus-ring ring-offset-2 ring-offset-canvas' : ''"
                style="background: {{ $option['value'] }}"
                class="size-7.5 cursor-pointer rounded-md border border-hairline-strong transition-shadow duration-150"
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

        <x-error :messages="$errors->get('colour')" class="mt-2" />
      </x-box>

      @if ($viewModel->showsTemplates())
        <x-box :title="__('What a new contact starts from')" :description="__('Every contact you add here begins with this template. You can change it afterwards, for the vault or for one contact.')">
          <x-select
            id="template_version_id"
            :label="__('Default template')"
            :error="$errors->get('template_version_id')"
            class="max-w-105"
          >
            @foreach ($viewModel->templates() as $template)
              <option value="{{ $template['id'] }}" @selected($values['templateVersionId'] === (string) $template['id'])>{{ $template['name'] }}</option>
            @endforeach
          </x-select>
        </x-box>
      @endif

      <x-box :title="__('Where new records go')" :description="__('One vault in your account is the default. Anything created without a vault chosen goes there.')">
        <div class="space-y-2">
          <x-checkbox
            id="is_default"
            x-model="isDefault"
            :checked="$values['isDefault']"
            :disabled="$viewModel->isFirstVault()"
          >
            {{ __('Make this the default vault') }}
          </x-checkbox>

          @if ($viewModel->isFirstVault())
            <p class="pl-6.5 text-xs leading-relaxed text-muted">{{ $viewModel->defaultHintWhenTicked() }}</p>
          @else
            <p
              class="pl-6.5 text-xs leading-relaxed text-muted"
              x-text="isDefault ? @js($viewModel->defaultHintWhenTicked()) : @js($viewModel->defaultHintWhenLeft())"
            >{{ $values['isDefault'] ? $viewModel->defaultHintWhenTicked() : $viewModel->defaultHintWhenLeft() }}</p>
          @endif
        </div>
      </x-box>
    </x-form>

    <div class="flex flex-wrap items-center gap-4 border-t border-hairline pt-5">
      <p class="max-w-105 text-xs leading-relaxed text-muted-soft">{{ __('You can rename a vault, recolour it and change its description later. Deleting one cannot be undone, and it asks first.') }}</p>

      <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full sm:ml-auto">
        <x-button.secondary :href="$viewModel->backUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

        <x-button form="vault-form" ::disabled="! named">{{ __('Create vault') }}</x-button>
      </div>
    </div>
  </div>
</x-account-layout>
