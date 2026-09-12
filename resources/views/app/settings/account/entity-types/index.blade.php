{{-- The kinds of record the account invented, beyond the people every account keeps. --}}
{{-- @var \App\ViewModels\Settings\Account\EntityTypes\EntityTypesViewModel $viewModel --}}
<x-settings.page
  :title="__('Kinds of record')"
  :user="$viewModel->user()"
  current="entity-types"
>
  <x-page-header :title="__('Kinds of record')" :description="__('A vault can hold more than people: animals, companies, cars, anything you want to keep track of. You decide what they are called and where they appear.')">
    <x-slot:actions>
      <x-button :href="route('settings.entityTypes.new')" data-turbo="true" class="max-sm:w-full">
        {{ __('Create a type') }}
      </x-button>
    </x-slot:actions>
  </x-page-header>

  <x-page-section
    :title="__('Your types')"
    :description="__('Drag a row to change the order they appear in the vault navigation.')"
  >
    <x-slot:aside>{{ $viewModel->summary() }}</x-slot:aside>

    <x-panel :flush="true">
      <div id="entity-types" x-merge="replace" x-data="sortableRows" data-sortable class="dim-when-busy">
        @forelse ($viewModel->types() as $type)
          @include('app.settings.account.entity-types._row', ['type' => $type])
        @empty
          <x-empty-state :title="__('No types yet')">
            <x-slot:icon>
              <x-icon name="organization" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ __('Make one for the thing you keep losing track of. It appears in every vault in this account.') }}
          </x-empty-state>
        @endforelse
      </div>
    </x-panel>

    <p class="px-0.5 text-xs text-pretty text-muted">{{ __('Turning a type off keeps its records and stops new ones being made.') }}</p>
  </x-page-section>
</x-settings.page>
