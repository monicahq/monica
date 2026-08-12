{{--
  The lists an account picks its values from, and everything somebody can do to
  them. Each list is a panel of its own, kept in a partial beside this file.

  The stack of panels is one ajax target, so adding a list and deleting one
  redraw the whole stack. Everything that happens inside a list redraws that
  list alone, which is why each panel carries an id of its own.

  The button that adds a list sits beside the title, where every other screen
  keeps the one thing it makes, and raises a card to ask for the name. The card
  is inside the target: adding a list has to leave it closed, and the flag that
  opens it is set in the browser, so it has to come back closed from the server.
  A rejected name is what reopens it, which is why it reads the error bag.

  @var \App\ViewModels\Settings\Account\DefaultValues\DefaultValuesViewModel $viewModel
--}}
@php
  $newListErrors = $errors->getBag('newList');
@endphp
<x-shell :title="__('Default values')">
  <x-slot:bar>
    <x-button.bar :href="route('settings.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </x-button.bar>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <div
    id="value-lists"
    x-merge="replace"
    class="flex flex-col gap-8 transition-opacity [&[aria-busy]]:opacity-60"
  >
    <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
      <div class="flex min-w-0 flex-col gap-2">
        <h1 class="font-display text-page-title text-ink">{{ __('Default values') }}</h1>

        <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('The lists you pick from when you fill in a contact. Rename them to the words you actually use, and put them in the order you want to see them.') }}</p>
      </div>

      <x-modal.form
        :title="__('Add a list')"
        :description="__('Something the fields of your contacts will pick from, such as how you met somebody.')"
        :confirm="__('Add the list')"
        :action="route('settings.valueLists.create')"
        :open="$newListErrors->has('name')"
        x-target="value-lists"
      >
        <x-slot:trigger>
          <x-button type="button" @click="open = true" class="max-sm:w-full">
            {{ __('Add a list') }}
          </x-button>
        </x-slot:trigger>

        <x-input
          id="name"
          :label="__('Name of the list')"
          :error="$newListErrors->get('name')"
          maxlength="255"
          required
        />
      </x-modal.form>
    </header>

    @foreach ($viewModel->lists() as $list)
      @include('app.settings.account.default-values._list', ['list' => $list])
    @endforeach

    <p class="px-0.5 text-xs text-pretty text-muted">{{ __('A value nothing uses is deleted outright. One that records already point at is stopped from being offered instead, so everything that already has it keeps it.') }}</p>
  </div>
</x-shell>
