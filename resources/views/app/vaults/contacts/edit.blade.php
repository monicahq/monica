{{--
  What the template has no say over about a contact: what they are called, the
  picture they are drawn as, and whether they are on the record at all.

  None is a field, which is why none is edited among them. The name is there
  whatever template somebody chose, the picture is drawn wherever the person
  appears, and deletion is not a question about the person at all. They share a
  screen because they are the same errand: the record offers one way in, and this
  is what is behind it.

  @var \App\ViewModels\Vaults\ContactEditViewModel $viewModel
--}}
@php
  $vault = $viewModel->vault();
  $contact = $viewModel->contact();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[['label' => $vault->name, 'url' => route('vaults.show', $vault)], ['label' => __('People'), 'url' => route('contacts.index', $vault)],
        ['label' => $contact->name, 'url' => $viewModel->contactUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

  {{--
    The picture, in a panel of its own before the name and outside the form that
    saves it, since a form cannot hold another.

    Its two forms reload the screen rather than updating it in place, the way the
    rest of this one does: a picture is read, turned upright, cropped and written
    before there is anything to show, and the screen is better off asking the
    server for the whole thing once that is done.

    The message lives in the x-data of this div rather than in the change handler,
    because Blade does not compile a directive written inside an attribute of a
    component tag: @js() there would reach the browser as itself and Alpine would
    refuse the whole expression.
  --}}
  <x-panel
    :title="__('Picture')"
    :subtitle="$viewModel->avatarDescription()"
    icon="profile"
  >
    <div
      class="flex flex-wrap items-center gap-5"
      x-data="{ tooBig: false, tooBigMessage: @js(__('The image must be under 5 MB.')) }"
    >
      <x-contact-avatar :name="$contact->name" :url="$viewModel->avatarUrl()" :size="72" />

      <div class="flex min-w-0 flex-1 flex-col gap-3">
        <x-form
          method="post"
          :action="$viewModel->updateAvatarUrl()"
          :upload="true"
          id="avatar-form"
          class="flex flex-wrap items-center gap-2.5"
          x-on:submit="if (tooBig) $event.preventDefault()"
        >
          {{--
            The field carries its name rather than a visible label: what is drawn
            beside it is the browser's own button, which is already worded and
            already translated.
          --}}
          <x-file-input
            id="avatar"
            accept="image/jpeg,image/png,image/webp"
            :error="$errors->get('avatar')"
            required
            class="w-auto"
            aria-label="{{ __('Choose a picture') }}"
            x-on:change="tooBig = window.oversizedFiles($event.target.files, 5120).length > 0"
          />

          <x-button size="small">{{ __('Upload') }}</x-button>
        </x-form>

        <p x-show="tooBig" x-cloak x-text="tooBigMessage" class="text-xs text-error"></p>

        @if ($viewModel->hasAvatar())
          {{--
            The card in front of it is what actually removes the picture, the same
            way the deletion below asks. Nothing in the product goes on the first
            click.
          --}}
          <x-modal.warning
            :title="__('Remove the picture of :name?', ['name' => $contact->name])"
            :confirm="__('Remove the picture')"
            :action="$viewModel->destroyAvatarUrl()"
          >
            <x-slot:trigger>
              <x-button.ghost type="button" size="small" class="self-start" @click="open = true">
                {{ __('Remove the picture') }}
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
      :subtitle="$viewModel->nameDescription()"
      icon="people"
    >
      <div class="grid gap-4 sm:grid-cols-2">
        <x-input
          id="prefix"
          :value="$viewModel->value('prefix')"
          :label="__('Prefix')"
          optional
          :placeholder="__('Dr, Mrs')"
          :error="$errors->get('prefix')"
          maxlength="50"
        />

        <x-input
          id="suffix"
          :value="$viewModel->value('suffix')"
          :label="__('Suffix')"
          optional
          :placeholder="__('Jr, PhD')"
          :error="$errors->get('suffix')"
          maxlength="50"
        />

        <x-input
          id="first_name"
          :value="$viewModel->value('first_name')"
          :label="__('First name')"
          :error="$errors->get('first_name')"
          maxlength="255"
        />

        <x-input
          id="middle_name"
          :value="$viewModel->value('middle_name')"
          :label="__('Middle name')"
          optional
          :error="$errors->get('middle_name')"
          maxlength="255"
        />

        <x-input
          id="last_name"
          :value="$viewModel->value('last_name')"
          :label="__('Last name')"
          :error="$errors->get('last_name')"
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

        <x-input
          id="nickname"
          :value="$viewModel->value('nickname')"
          :label="__('Nickname')"
          optional
          :help="__('What they are actually called.')"
          :error="$errors->get('nickname')"
          maxlength="255"
        />

        <x-select
          id="name_order"
          :label="__('Reads as')"
          optional
          :help="__('Leave it alone to follow your own preference.')"
          :error="$errors->get('name_order')"
        >
          <option value="" @selected($viewModel->followsThePreference())>{{ __('Follow my preference') }}</option>

          @foreach ($viewModel->nameOrders() as $order)
            <option value="{{ $order['value'] }}" @selected($order['isSelected'])>{{ $order['label'] }}</option>
          @endforeach
        </x-select>
      </div>
    </x-panel>

    <div class="flex flex-wrap items-center justify-end gap-2">
      <x-button.secondary :href="$viewModel->contactUrl()" data-turbo="true">{{ __('Cancel') }}</x-button.secondary>

      <x-button type="submit">{{ __('Save the name') }}</x-button>
    </div>
  </x-form>

  {{--
    The way out. It is a panel of its own at the foot of the screen rather than a
    button beside the one that saves, so that nothing about leaving looks like
    part of filling in.

    The card in front of it is what actually deletes. Nothing in the product goes
    on the first click.
  --}}
  <x-panel :title="__('Delete contact')" icon="people">
    <div class="flex flex-wrap items-center gap-3.5">
      <p class="max-w-[60ch] text-sm text-pretty text-muted">{{ $viewModel->deleteDescription() }}</p>

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

          <p>{{ __('Everything written on the record goes with them.') }}</p>

          <p>{{ __('Nothing they took part in is destroyed, and they can be brought back.') }}</p>
        </x-modal.warning>
      @endif
    </div>
  </x-panel>
</x-shell>
