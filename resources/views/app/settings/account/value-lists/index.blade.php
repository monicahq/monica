{{-- The lists an account picks its values from, and everything somebody can do to them. --}}
{{-- @var \App\ViewModels\Settings\Account\ValueLists\ValueListsViewModel $viewModel --}}
@php
  $newListErrors = $errors->getBag('newList');
@endphp
<x-settings.page
  :title="__('Value lists')"
  :user="$viewModel->user()"
  current="value-lists"
>
  <div
    id="value-lists"
    x-merge="replace"
    class="flex flex-col gap-8 dim-when-busy"
  >
    <x-page-header :title="__('Value lists')" :description="__('The lists you pick from when you fill in a contact. Rename them to the words you actually use, and put them in the order you want to see them.')">
      <x-slot:actions>
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
      </x-slot:actions>
    </x-page-header>

    @foreach ($viewModel->lists() as $list)
      @include('app.settings.account.value-lists._list', ['list' => $list])
    @endforeach

    <p class="px-0.5 text-xs text-pretty text-muted">{{ __('A value nothing uses is deleted. A value records already point at is no longer offered instead, so everything that already has it keeps it.') }}</p>
  </div>
</x-settings.page>
