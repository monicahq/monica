{{--
  The kinds of record the account invented, beyond the people every account
  keeps. This screen names them and orders them; what one of them holds is a
  template, and that is described elsewhere.

  The whole list is one ajax target, since dragging a row renumbers every row
  around it and a swap of the panel is what shows that.

  @var \App\ViewModels\Settings\Account\EntityTypes\EntityTypesViewModel $viewModel
--}}
<x-app-layout :title="__('Entity types')">
  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="entities" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Entity types') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <div class="grid items-start gap-x-7 gap-y-4 sm:grid-cols-[minmax(0,1fr)_auto]">
    <x-page-header
      :title="__('Entity types')"
      :description="__('Beyond people, a vault can hold animals, companies, cars, anything you want to keep track of. You decide what those things are called and where they appear.')"
      class="max-w-140"
    />

    <x-button :href="route('settings.entityTypes.new')" data-turbo="true" class="max-sm:w-full sm:justify-self-end">
      {{ __('Create a type') }}
    </x-button>
  </div>

  <div class="space-y-2">
    <x-box padding="p-0">
      <x-slot:title>
        {{ __('Your types') }}

        <span class="ml-1.5 text-xs font-normal text-muted-soft">{{ $viewModel->summary() }}</span>
      </x-slot:title>

      <x-slot:help>
        <x-help :title="__('Entity types')">
          {{ __('A type is only the noun. What one of them holds is described by a template, and the same type can be described differently in two vaults: the family vault and a breeding vault both keep animals, at completely different levels of detail.') }}
        </x-help>
      </x-slot:help>

      <x-slot:description>
        <p>{{ __('Drag a row to change the order they appear in the vault navigation.') }}</p>
      </x-slot:description>

      <div id="entity-types" x-merge="replace" x-data="sortableRows" data-sortable class="transition-opacity [&[aria-busy]]:opacity-60">
        @forelse ($viewModel->types() as $type)
          @include('app.settings.account.entity-types._row', ['type' => $type])
        @empty
          <x-empty-state :title="__('No types yet')">
            <x-slot:icon>
              <x-entity-icon name="device" :size="20" />
            </x-slot:icon>

            {{ __('Make one for the thing you keep losing track of. It appears in every vault in this account.') }}
          </x-empty-state>
        @endforelse
      </div>
    </x-box>

    <p class="text-xs leading-relaxed text-muted-soft">{{ __('Turning a type off keeps its records and stops new ones being made.') }}</p>
  </div>
</x-app-layout>
