{{--
  The two things about a contact the template has no say over: what they are
  called, and whether they are on the record at all.

  Neither is a field, which is why neither is edited among them. The name is
  there whatever template somebody chose, and deletion is not a question about
  the person at all. They share a screen because they are the same errand: the
  record offers one way in, and this is what is behind it.

  @var \App\ViewModels\Vaults\ContactEditViewModel $viewModel
--}}
@php
  $vault = $viewModel->vault();
  $contact = $viewModel->contact();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:bar>
    <x-vault.bar
      :vault="$vault"
      :items="[
        ['label' => __('People'), 'icon' => 'people', 'url' => route('contacts.index', $vault)],
        ['label' => $contact->name, 'url' => $viewModel->contactUrl()],
        ['label' => $viewModel->title()],
      ]"
    />
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" :vault="$vault" current="people" />
  </x-slot:dock>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->description()" />

  <x-form id="name-form" method="put" :action="$viewModel->action()" class="flex flex-col gap-5">
    <x-panel
      :title="__('Change the name')"
      :subtitle="$viewModel->nameDescription()"
      icon="people"
      icon-class="bg-indigo-tint text-indigo"
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
  <x-panel :title="__('Delete contact')" icon="people" icon-class="bg-error-tint text-error">
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
