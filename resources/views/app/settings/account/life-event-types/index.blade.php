{{-- The natures a life event can be recorded as, and the categories they are filed under. --}}
{{-- @var \App\ViewModels\Settings\Account\LifeEventTypes\LifeEventTypesViewModel $viewModel --}}
@php
  $newCategoryErrors = $errors->getBag('newCategory');
  $selected = $viewModel->selectedCategory();
  $rows = $viewModel->rows();
@endphp
<x-settings.page
  :title="__('Life event types')"
  :user="$viewModel->user()"
  current="life-event-types"
>
  <div
    id="life-event-types"
    x-merge="replace"
    class="flex flex-col gap-8 dim-when-busy"
  >
    <x-page-header
      :title="__('Life event types')"
    >
      <x-slot:actions>
        <x-modal.form
          :title="__('Add a category')"
          :description="__('A group the life event types are listed under. It decides nothing else about them.')"
          :confirm="__('Add the category')"
          :action="route('settings.lifeEventCategories.create')"
          :open="$newCategoryErrors->has('name')"
          x-target="life-event-types"
        >
          <x-slot:trigger>
            <x-button.secondary type="button" @click="open = true" class="max-sm:w-full">
              {{ __('Add a category') }}
            </x-button.secondary>
          </x-slot:trigger>

          <x-input
            id="name"
            :label="__('Name of the category')"
            :placeholder="__('Music and choir')"
            :error="$newCategoryErrors->get('name')"
            maxlength="255"
            required
          />
        </x-modal.form>

        <x-button :href="$viewModel->addUrl()" data-turbo="true" class="max-sm:w-full">
          {{ __('Add a type') }}
        </x-button>
      </x-slot:actions>
    </x-page-header>

    <div class="flex flex-wrap items-start gap-6">
      <div class="w-full min-w-60 shrink-0 lg:w-72">
        @include('app.settings.account.life-event-types._rail', [
          'rail' => $viewModel->rail(),
          'showsEveryType' => $viewModel->showsEveryType(),
          'totalCount' => $viewModel->totalCount(),
        ])
      </div>

      <div class="flex min-w-0 flex-1 basis-100 flex-col gap-3.5">
        <div class="flex min-h-9 flex-wrap items-center gap-x-4 gap-y-2">
          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2.5">
            <h2 class="text-section-title text-ink">{{ $viewModel->panelTitle() }}</h2>

            @if ($viewModel->selectedIsInactive())
              <x-badge tone="warning">{{ __('Not offered when recording') }}</x-badge>
            @endif
          </div>

          @isset($selected)
            @include('app.settings.account.life-event-types._category-actions', ['category' => $selected])
          @endisset
        </div>

        <x-list-panel :items="$rows">
          <x-slot:empty>
            <x-empty-state
              :title="$viewModel->showsEveryType() ? __('No type yet') : __('Nothing filed here yet')"
              :href="$viewModel->addUrl()"
              :link-label="__('Add a type')"
            >
              {{ $viewModel->showsEveryType()
                  ? __('A life event is recorded as something. Add the first type and it is offered the next time you write one down.')
                  : __('A type you add under this category is offered wherever the category is.') }}
            </x-empty-state>
          </x-slot:empty>

          <div x-data="sortableRows" data-sortable>
            @foreach ($rows as $type)
              @include('app.settings.account.life-event-types._row', [
                'type' => $type,
                'showsEveryType' => $viewModel->showsEveryType(),
              ])
            @endforeach
          </div>
        </x-list-panel>
      </div>
    </div>
  </div>
</x-settings.page>
