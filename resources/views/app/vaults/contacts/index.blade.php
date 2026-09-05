{{-- Everybody in one vault, on one list. --}}
{{-- @var \App\ViewModels\Vaults\ContactsViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[['label' => $vault->name, 'url' => route('vaults.show', $vault)], ['label' => __('People')]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
    <h1 class="min-w-0 text-page-title text-ink">{{ $viewModel->title() }}</h1>

    <x-button :href="$viewModel->newContactUrl()" data-turbo="true" size="small">
      <x-slot:icon>
        <x-icon name="plus" :size="16" :stroke-width="1.6" />
      </x-slot:icon>

      {{ __('Add someone') }}
    </x-button>
  </header>

  <div id="contacts" x-merge="replace" class="flex flex-col gap-3.5">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2.5">
      <div class="flex flex-wrap items-center gap-2">
        @foreach ($viewModel->filters() as $filter)
          <x-chip
            :href="$filter['url']"
            :selected="$filter['isCurrent']"
            :icon="$filter['icon']"
            :count="$filter['count']"
            x-target="contacts"
          >{{ $filter['label'] }}</x-chip>
        @endforeach
      </div>

      <form method="get" action="{{ $viewModel->searchUrl() }}" x-target="contacts" class="flex flex-wrap items-center gap-2 sm:ml-auto">
        <input type="hidden" name="query" value="{{ $viewModel->query() }}" />

        <label for="sort" class="text-xs text-pretty text-muted">{{ __('Sorted by') }}</label>

        <x-select id="sort" size="small" class="w-38">
          @foreach ($viewModel->sorts() as $sort)
            <option value="{{ $sort['value'] }}" @selected($sort['isCurrent'])>{{ $sort['label'] }}</option>
          @endforeach
        </x-select>

        <x-select id="direction" size="small" :aria-label="__('Which way round the order runs')" class="w-36">
          @foreach ($viewModel->directions() as $direction)
            <option value="{{ $direction['value'] }}" @selected($direction['isCurrent'])>{{ $direction['label'] }}</option>
          @endforeach
        </x-select>

        <x-button.secondary size="small">{{ __('Order the list') }}</x-button.secondary>
      </form>
    </div>

    <div class="flex flex-wrap items-center gap-x-4 gap-y-2.5">
      <form method="get" action="{{ $viewModel->searchUrl() }}" x-target="contacts" class="flex items-center gap-2">
        <input type="hidden" name="sort" value="{{ $viewModel->sort() }}" />
        <input type="hidden" name="direction" value="{{ $viewModel->direction() }}" />

        <x-input
          type="search"
          id="query"
          size="small"
          :value="$viewModel->query()"
          :placeholder="__('Search names and nicknames')"
          :aria-label="__('Search the people in this vault')"
          class="w-60"
        />

        <x-button.secondary size="small">{{ __('Search') }}</x-button.secondary>
      </form>

      <p class="sm:ml-auto text-sm text-pretty text-muted">{{ $viewModel->resultLine() }}</p>
    </div>

    <x-panel :flush="true">
      @if ($viewModel->vaultIsEmpty())
        <x-empty-state :title="__('Nobody here yet')">
          <x-slot:icon>
            <x-icon name="people" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ $viewModel->emptyLine() }}

          <x-slot:action>
            <x-button :href="$viewModel->newContactUrl()" data-turbo="true">
              <x-slot:icon>
                <x-icon name="plus" :size="18" :stroke-width="1.6" />
              </x-slot:icon>

              {{ __('Write somebody down') }}
            </x-button>
          </x-slot:action>
        </x-empty-state>
      @elseif ($viewModel->noResults())
        <div class="flex min-h-60 flex-col items-center justify-center gap-3.5 px-8 py-12 text-center">
          <p class="text-section-title text-ink">{{ __('Nothing matches') }}</p>

          <p class="max-w-[44ch] text-base text-pretty text-muted">{{ __('Try a shorter search, or another chip above.') }}</p>
        </div>
      @else
        <div id="contact-rows" x-merge="append">
          @include('app.vaults.contacts._groups')
        </div>

        <x-slot:footer id="pagination" class="flex flex-wrap items-center gap-3">
          @if ($viewModel->hasMorePages())
            <x-link x-target="contact-rows pagination" :href="$viewModel->nextPageUrl()">{{ __('Load more') }}</x-link>
          @else
            <span class="text-xs text-pretty text-muted">{{ $viewModel->endLine() }}</span>
          @endif

          <x-button.secondary :href="$viewModel->newContactUrl()" data-turbo="true" size="small" class="ml-auto">
            <x-slot:icon>
              <x-icon name="plus" :size="16" :stroke-width="1.6" />
            </x-slot:icon>

            {{ __('Add someone') }}
          </x-button.secondary>
        </x-slot:footer>
      @endif
    </x-panel>
  </div>
</x-shell>
