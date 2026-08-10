{{--
  The lists an account picks its values from, and everything somebody can do to
  them. Each list is a panel of its own, kept in a partial beside this file.

  The stack of panels is one ajax target, so adding a list and deleting one
  redraw the whole stack. Everything that happens inside a list redraws that
  list alone, which is why each panel carries an id of its own.

  The button that adds a list sits beside the title, where every other screen
  keeps the one thing it makes. The form it opens comes underneath rather than
  up there with it, since a field and two buttons do not fit in a corner. Both
  are inside the target: adding a list has to leave the form closed, and the
  flag that opens it is set in the browser, so it has to come back closed from
  the server.

  @var \App\ViewModels\Settings\Account\DefaultValues\DefaultValuesViewModel $viewModel
--}}
@php
  $newListErrors = $errors->getBag('newList');
@endphp
<x-app-layout :title="__('Default values')">
  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="defaults" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <x-breadcrumb
      :items="[
        ['label' => __('Settings')],
        ['label' => __('Default values')],
      ]"
    />
  </x-slot:breadcrumb>

  <div
    id="value-lists"
    x-merge="replace"
    x-data="{ adding: {{ $newListErrors->has('name') ? 'true' : 'false' }} }"
    class="space-y-8.5 transition-opacity [&[aria-busy]]:opacity-60"
  >
    <div class="grid items-start gap-x-7 gap-y-4 sm:grid-cols-[minmax(0,1fr)_auto]">
      <x-page-header
        :title="__('Default values')"
        :description="__('The lists you pick from when you fill in a contact. Rename them to the words you actually use, and put them in the order you want to see them.')"
        class="max-w-140"
      />

      <x-button
        type="button"
        x-show="! adding"
        @click="adding = true; $nextTick(() => $refs.name.focus())"
        class="max-sm:w-full sm:justify-self-end"
      >
        {{ __('Add a list') }}
      </x-button>
    </div>

    <x-form
      x-cloak
      x-show="adding"
      method="post"
      :action="route('settings.valueLists.create')"
      x-target="value-lists"
      class="flex flex-wrap items-start gap-3"
    >
      <x-input
        id="name"
        x-ref="name"
        :label="__('Name of the list')"
        :help="__('Something the fields of your contacts will pick from, such as how you met somebody.')"
        :error="$newListErrors->get('name')"
        class="max-w-80"
        maxlength="255"
        required
      />

      <div class="flex items-center gap-2 pt-6.5">
        <x-button>{{ __('Add the list') }}</x-button>

        <button type="button" @click="adding = false" class="cursor-pointer rounded-md px-2 py-1.5 text-sm text-muted transition-colors duration-150 hover:bg-hover hover:text-ink">{{ __('Cancel') }}</button>
      </div>
    </x-form>

    @foreach ($viewModel->lists() as $list)
      @include('app.settings.account.default-values._list', ['list' => $list])
    @endforeach

    <p class="text-xs leading-relaxed text-muted-soft">{{ __('A value nothing uses is deleted outright. One that records already point at is stopped from being offered instead, so everything that already has it keeps it.') }}</p>
  </div>
</x-app-layout>
