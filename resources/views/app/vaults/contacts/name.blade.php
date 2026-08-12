{{--
  The form that changes what somebody is called.

  It is a screen of its own rather than a row among the fields, because the name
  is not one of them: every other question on a record comes from a structure
  somebody chose, and this one is there whatever they choose.

  @var \App\ViewModels\Vaults\ContactNameViewModel $viewModel
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
    <x-panel :title="__('Name')" icon="people" icon-class="bg-indigo-tint text-indigo">
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
</x-shell>
