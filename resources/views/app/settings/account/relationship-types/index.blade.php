{{-- The links this account can put between two people, and the words each side is described with. --}}
{{-- @var \App\ViewModels\Settings\Account\RelationshipTypes\RelationshipTypesViewModel $viewModel --}}
@php
  $newCategoryErrors = $errors->getBag('newCategory');
@endphp
<x-settings.page
  :title="__('Relationship types')"
  :user="$viewModel->user()"
  current="relationship-types"
>
  <div
    id="relationship-types"
    x-merge="replace"
    class="flex flex-col gap-8 dim-when-busy"
  >
    <x-page-header :title="__('Relationship types')" :description="__('The links you can put between two people, and the words each side is described with. Rename them, reorder them, or stop offering the ones you never use.')">
      <x-slot:actions>
        <x-modal.form
          :title="__('Add a category')"
          :description="__('A group the relationship types are listed under. It decides nothing else about them.')"
          :confirm="__('Add the category')"
          :action="route('settings.relationshipCategories.create')"
          :open="$newCategoryErrors->has('name')"
          x-target="relationship-types"
        >
          <x-slot:trigger>
            <x-button.secondary type="button" @click="open = true" class="max-sm:w-full">
              {{ __('Add a category') }}
            </x-button.secondary>
          </x-slot:trigger>

          <x-input
            id="name"
            :label="__('Name of the category')"
            :placeholder="__('Neighbourhood')"
            :error="$newCategoryErrors->get('name')"
            maxlength="255"
            required
          />
        </x-modal.form>

        <x-button :href="route('settings.relationshipTypes.new')" data-turbo="true" class="max-sm:w-full">
          {{ __('Add a type') }}
        </x-button>
      </x-slot:actions>
    </x-page-header>

    <div class="flex flex-col gap-7">
      @foreach ($viewModel->categories() as $category)
        @include('app.settings.account.relationship-types._category', ['category' => $category])
      @endforeach
    </div>

    <x-notice tone="quiet">
      {{ __('A few supplied types are used by the family tree and by romantic history. You can rename them, and what they mean stays the same. The categories that came with your account cannot be renamed or deleted. The ones you add are yours to change.') }}
    </x-notice>
  </div>
</x-settings.page>
