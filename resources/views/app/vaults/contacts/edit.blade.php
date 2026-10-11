{{-- What the template has no say over about a contact: their name, their picture, their existence. --}}
{{-- @var \App\ViewModels\Vaults\ContactEditViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $contact = $viewModel->contact();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('People'), 'url' => route('contacts.index', $vault)],
        ['label' => $contact->name, 'url' => $viewModel->contactUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

  <x-panel
    :title="__('Photo')"
    :subtitle="$viewModel->avatarDescription()"
    icon="profile"
  >
    <div
      class="flex flex-wrap items-center gap-5"
      x-data="{ tooBig: false, tooBigMessage: @js(__('The photo must be under 5 MB.')) }"
    >
      <x-contact-avatar :name="$contact->name" :url="$viewModel->avatarUrl()" :size="72" />

      <div class="flex min-w-0 flex-1 flex-col gap-3">
        @if ($viewModel->uploadsAreOn())
          <x-form
            method="post"
            :action="$viewModel->updateAvatarUrl()"
            :upload="true"
            id="avatar-form"
            class="flex flex-wrap items-center gap-2.5"
            x-on:submit="if (tooBig) $event.preventDefault()"
          >
            <x-file-input
              id="avatar"
              accept="image/jpeg,image/png,image/webp"
              :error="$errors->get('avatar')"
              required
              class="w-auto"
              aria-label="{{ __('Choose a photo') }}"
              x-on:change="tooBig = window.oversizedFiles($event.target.files, 5120).length > 0"
            />

            <x-button size="small">{{ __('Upload') }}</x-button>
          </x-form>

          <p x-show="tooBig" x-cloak x-text="tooBigMessage" class="text-xs text-error"></p>
        @elseif ($viewModel->hasAvatar())
          <x-notice tone="quiet">{{ __('Uploads are unavailable on this account for now.') }}</x-notice>
        @endif

        @if ($viewModel->hasAvatar())
          <x-modal.warning
            :title="__('Remove the photo of :name?', ['name' => $contact->name])"
            :confirm="__('Remove the photo')"
            :action="$viewModel->destroyAvatarUrl()"
          >
            <x-slot:trigger>
              <x-button.ghost type="button" size="small" class="self-start" @click="open = true">
                {{ __('Remove the photo') }}
              </x-button.ghost>
            </x-slot:trigger>

            <p>{{ __('They are drawn as their initials again.') }}</p>
          </x-modal.warning>
        @endif
      </div>
    </div>
  </x-panel>

  <x-form id="name-form" method="put" :action="$viewModel->action()" class="flex flex-col gap-5">
    <x-panel
      :title="__('Change the name')"
      icon="people"
    >
      <div class="flex flex-col gap-6">
        <x-contact-name-editor :block="$viewModel->nameEditor()" :short-name="$viewModel->shortName()" />

        <div class="h-px bg-hairline"></div>

        <div class="grid gap-4 sm:grid-cols-2">
          <x-input
            id="nickname"
            :value="$viewModel->value('nickname')"
            :label="__('Nickname')"
            optional
            :help="__('What they are actually called.')"
            :error="$errors->get('nickname')"
            maxlength="255"
          />

          <x-input
            id="maiden_name"
            :value="$viewModel->value('maiden_name')"
            :label="__('Name before marriage')"
            optional
            :error="$errors->get('maiden_name')"
            maxlength="255"
          />
        </div>
      </div>
    </x-panel>

    <div class="flex flex-wrap items-center justify-end gap-2" x-data="{ held: false }" @name-editor-busy.window="held = $event.detail">
      <p id="name-form-held" x-show="held" x-cloak class="text-sm text-muted">{{ __('Finish the change to the name first, or cancel it.') }}</p>

      <x-button.secondary :href="$viewModel->contactUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

      <x-button type="submit" ::aria-disabled="held" aria-describedby="name-form-held">{{ __('Save the name') }}</x-button>
    </div>
  </x-form>

  <x-panel :title="__('Delete contact')" icon="people">
    <div class="flex flex-wrap items-center gap-3.5">
      <p id="delete-contact-reason" class="max-w-prose text-sm text-pretty text-muted">{{ $viewModel->deleteDescription() }}</p>

      @if ($viewModel->mayBeDeleted())
        <x-modal.warning
          :title="__('Delete :name?', ['name' => $contact->name])"
          :confirm="__('Delete the contact')"
          :action="$viewModel->destroyUrl()"
        >
          <x-slot:trigger>
            <x-button.secondary type="button" @click="open = true" class="text-error sm:ml-auto">
              {{ __('Delete the contact') }}
            </x-button.secondary>
          </x-slot:trigger>

          <p>{{ __('Everything written on the record is deleted with them.') }}</p>
        </x-modal.warning>
      @else
        <x-button.secondary
          type="button"
          aria-disabled="true"
          aria-describedby="delete-contact-reason"
          class="sm:ml-auto"
        >
          {{ __('Delete the contact') }}
        </x-button.secondary>
      @endif
    </div>
  </x-panel>
</x-shell>
