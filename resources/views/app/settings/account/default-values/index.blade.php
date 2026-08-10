{{--
  The lists an account picks its values from, and everything somebody can do to
  them. Each list is a panel of its own, kept in a partial beside this file.

  The stack of panels is one ajax target, so adding a list and deleting one
  redraw the whole stack. Everything that happens inside a list redraws that
  list alone, which is why each panel carries an id of its own.

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

  <x-page-header
    :title="__('Default values')"
    :description="__('The lists you pick from when you fill in a contact. Rename them to the words you actually use, and put them in the order you want to see them.')"
  />

  <div
    id="value-lists"
    x-merge="replace"
    x-data="{ adding: {{ $newListErrors->has('name') ? 'true' : 'false' }} }"
    class="space-y-8.5 transition-opacity [&[aria-busy]]:opacity-60"
  >
    @foreach ($viewModel->lists() as $list)
      @include('app.settings.account.default-values._list', ['list' => $list])
    @endforeach

    <div class="space-y-3">
      <x-button.secondary type="button" x-show="! adding" @click="adding = true; $nextTick(() => $refs.name.focus())">
        <x-slot:icon>
          <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">
            <line x1="8" y1="3.2" x2="8" y2="12.8"></line>
            <line x1="3.2" y1="8" x2="12.8" y2="8"></line>
          </svg>
        </x-slot:icon>

        {{ __('Add a list') }}
      </x-button.secondary>

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

      <p class="text-xs leading-relaxed text-muted-soft">{{ __('A value nothing uses is deleted outright. One that records already point at is stopped from being offered instead, so everything that already has it keeps it.') }}</p>
    </div>
  </div>
</x-app-layout>
