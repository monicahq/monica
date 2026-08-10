{{--
  The settings of one vault. Two things for now: what a contact written down
  here starts from, and the way to be rid of the vault.

  The template section only asks a question when there is one to ask. An account
  with a single contact template has nothing to choose between, so the screen
  names what the vault uses and points at where more templates are made.

  @var \App\ViewModels\Vaults\VaultSettingsViewModel $viewModel
--}}
@php
  $vault = $viewModel->vault();
@endphp
<x-app-layout :title="$viewModel->title()">
  <x-slot:sidebar>
    <x-vault.sidebar
      :vault="$vault"
      :vaults="$viewModel->vaults()"
      :user="$viewModel->user()"
      current="vault-settings"
    />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <x-breadcrumb
      :items="[
        ['label' => __('Your vaults'), 'url' => $viewModel->vaultsUrl()],
        ['label' => $vault->name, 'url' => $viewModel->vaultUrl()],
        ['label' => __('Settings')],
      ]"
    />
  </x-slot:breadcrumb>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" class="max-w-140" />

  <x-box
    :title="__('What a new contact starts from')"
    :description="__('Every contact added to this vault begins with this template. The ones already here keep the version they were written on.')"
  >
    @if ($viewModel->hasTemplateChoice())
      <x-form method="put" :action="$viewModel->defaultTemplateAction()" class="space-y-3.5">
        <div class="max-w-105 space-y-1.5">
          <x-label for="template_version_id" :value="__('Default template')" />

          <select
            id="template_version_id"
            name="template_version_id"
            class="block w-full appearance-none rounded-md border border-hairline-strong bg-input px-3 py-2.5 text-base text-ink sm:text-sm"
          >
            @foreach ($viewModel->templates() as $template)
              <option value="{{ $template['id'] }}" @selected($viewModel->currentTemplateVersionId() === (string) $template['id'])>{{ $template['name'] }}</option>
            @endforeach
          </select>

          <x-error :messages="$errors->get('template_version_id')" />
        </div>

        <x-button>{{ __('Save') }}</x-button>
      </x-form>
    @else
      <div class="space-y-3.5">
        @if ($viewModel->currentTemplateName())
          <p class="text-sm leading-relaxed text-body">
            {{ __('Contacts here start from :name, which is the only contact template your account has.', ['name' => $viewModel->currentTemplateName()]) }}
          </p>
        @else
          <p class="text-sm leading-relaxed text-body">
            {{ __('Nothing is set, so whoever writes a contact down here picks a template themselves.') }}
          </p>
        @endif

        <p class="text-sm leading-relaxed text-muted">
          {{ __('Make another one and this becomes a choice.') }}
          <x-link :href="$viewModel->templatesUrl()" turbo>{{ __('Your templates') }}</x-link>
        </p>
      </div>
    @endif
  </x-box>

  {{--
    The destructive half. It asks twice: the button raises the card, and the
    card says what the vault holds before it goes.
  --}}
  <x-box :title="__('Delete this vault')">
    <div class="grid gap-9 md:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
      <div class="space-y-2.5 text-sm leading-relaxed text-body">
        <p>{{ __('Everything filed in this vault goes with it: the contacts, and everything written about them.') }}</p>

        <p>{{ __('Nothing in the application brings a vault back, so this is the end of it.') }}</p>
      </div>

      <div>
        <x-modal.warning
          :title="__('Delete “:name”?', ['name' => $vault->name])"
          :confirm="__('Delete the vault')"
          :cancel="__('Keep it')"
          :action="$viewModel->destroyAction()"
        >
          <x-slot:trigger>
            <x-button.secondary type="button" @click="open = true" class="max-sm:w-full">
              {{ __('Delete this vault') }}
            </x-button.secondary>
          </x-slot:trigger>

          <p>
            {{ $viewModel->contactCount() === 1
              ? __('The one contact in it goes too, and everything written about them.')
              : __('The :count contacts in it go too, and everything written about them.', ['count' => $viewModel->contactCount()]) }}
          </p>

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
    </div>
  </x-box>
</x-app-layout>
