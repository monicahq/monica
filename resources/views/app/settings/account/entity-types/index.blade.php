{{-- The kinds of record the account invented, beyond the people every account keeps. --}}
{{-- @var \App\ViewModels\Settings\Account\EntityTypes\EntityTypesViewModel $viewModel --}}
<x-shell :title="__('Entity types')">
  <x-slot:breadcrumb>
    <x-button.bar :href="route('settings.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="entity-types" />
  </x-slot:sidebar>

  <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
    <div class="flex min-w-0 flex-col gap-2">
      <h1 class="text-page-title text-ink">{{ __('Entity types') }}</h1>

      <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('Beyond people, a vault can hold animals, companies, cars, anything you want to keep track of. You decide what those things are called and where they appear.') }}</p>
    </div>

    <x-button :href="route('settings.entityTypes.new')" data-turbo="true" class="max-sm:w-full">
      {{ __('Create a type') }}
    </x-button>
  </header>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('Your types') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('Drag a row to change the order they appear in the vault navigation.') }}</p>

      <span class="ml-auto text-xs text-pretty text-muted">{{ $viewModel->summary() }}</span>
    </div>

    <x-panel :flush="true">
      <div id="entity-types" x-merge="replace" x-data="sortableRows" data-sortable class="transition-opacity [&[aria-busy]]:opacity-60">
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
  </section>
</x-shell>
