{{-- Everything written in a vault, or in every vault, that holds the words somebody typed. --}}
{{-- @var \App\ViewModels\Vaults\SearchViewModel $viewModel --}}
@php
  $status = $viewModel->status();
  $groups = $viewModel->groups();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('Search')]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="search" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" />

  <div class="space-y-7">
    <form method="get" action="{{ $viewModel->formUrl() }}" role="search" class="flex flex-col gap-3">
      <x-label for="query" :value="__('Search for')" />

      <div class="flex flex-wrap gap-2.5">
        <div class="relative flex min-w-65 flex-1 items-center">
          <x-icon name="search" :size="16" class="pointer-events-none absolute left-3 text-muted" />

          <input
            id="query"
            name="query"
            type="search"
            value="{{ $viewModel->query() }}"
            placeholder="{{ __('A name, a word, a detail') }}"
            autocomplete="off"
            aria-describedby="query-help"
            data-1p-ignore
            autofocus
            class="focus-ring-field h-9 w-full rounded-md border border-hairline-strong bg-input pr-3 pl-9 font-sans text-[16px] text-ink transition duration-(--duration-fast) ease-out placeholder:text-placeholder hover:border-muted-soft sm:text-sm"
          />
        </div>

        <x-button type="submit">{{ __('Search') }}</x-button>
      </div>

      <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
        <fieldset class="flex gap-1.5">
          <legend class="sr-only">{{ __('Where to search') }}</legend>

          @foreach (['vault' => __('This vault'), 'account' => __('Every vault')] as $scope => $label)
            <label class="group flex h-8 cursor-pointer items-center gap-2 rounded-md border border-hairline-strong px-3 text-sm font-medium text-ink hover:bg-hover has-checked:border-ink has-checked:bg-card has-focus-visible:ring-[3px] has-focus-visible:ring-focus-ring/45">
              <input
                type="radio"
                name="scope"
                value="{{ $scope }}"
                class="sr-only"
                @checked($viewModel->acrossAccount() === ($scope === 'account'))
              />
              <span class="size-3.5 shrink-0 rounded-full border border-hairline-strong group-has-checked:border-4 group-has-checked:border-ink" aria-hidden="true"></span>
              {{ $label }}
            </label>
          @endforeach
        </fieldset>

        <p id="query-help" class="text-sm text-muted">{{ __('Looks in names, field values, life event descriptions, and the names of linked people and records.') }}</p>
      </div>
    </form>

    @if ($status === \App\Actions\SearchRecords::TOO_SHORT)
      <p role="status" aria-live="polite" class="text-sm text-muted">{{ __('Type at least two letters or digits to search.') }}</p>
    @endif

    @if ($status === \App\Actions\SearchRecords::UNAVAILABLE)
      <x-notice tone="warning" :title="__('Search is unavailable right now')">
        {{ __('The search engine of this instance cannot be reached. Everything else works, and what you save now will be found once it is back.') }}
      </x-notice>
    @endif

    @if ($status === \App\ViewModels\Vaults\SearchViewModel::IDLE)
      <div class="rounded-lg border border-dashed border-hairline-strong">
        <x-empty-state :title="__('Nothing searched yet')">
          {{ __('Results are grouped by people, the other kinds of record in your vault, and life events.') }}
        </x-empty-state>
      </div>
    @endif

    @if ($status === \App\Actions\SearchRecords::SEARCHED)
      <section x-data="{ shown: 'all' }" aria-label="{{ __('Results') }}" class="flex flex-col gap-5">
        <div class="flex flex-col gap-3">
          <p role="status" aria-live="polite" class="text-sm text-muted">{{ $viewModel->countLine() }}</p>

          @if ($groups !== [])
            <div role="group" aria-label="{{ __('Show') }}" class="flex flex-wrap gap-2">
              <x-chip :selected="true" :count="$viewModel->resultCount()" x-bind:aria-pressed="shown === 'all'" @click="shown = 'all'">
                {{ __('All') }}
              </x-chip>

              @foreach ($groups as $group)
                <x-chip :count="$group['total']" x-bind:aria-pressed="shown === '{{ $group['key'] }}'" @click="shown = '{{ $group['key'] }}'">
                  {{ $group['label'] }}
                </x-chip>
              @endforeach
            </div>
          @endif
        </div>

        @if ($groups === [])
          <div class="rounded-lg border border-dashed border-hairline-strong">
            <x-empty-state :title="$viewModel->nothingFoundTitle()">
              {{ $viewModel->nothingFoundHint() }}
            </x-empty-state>
          </div>
        @endif

        @foreach ($groups as $group)
          <section
            x-show="shown === 'all' || shown === '{{ $group['key'] }}'"
            aria-labelledby="{{ $group['id'] }}-title"
            class="flex flex-col gap-2.5"
          >
            <div class="flex items-baseline gap-2.5 px-0.5">
              <h2 id="{{ $group['id'] }}-title" class="text-sm font-semibold text-ink">{{ $group['label'] }}</h2>
              <span class="text-sm text-muted">{{ $group['total'] }}</span>
            </div>

            <x-list-panel
              :items="$group['rows']"
              :id="$group['id']"
              merge="append"
              :target="$group['id'].' '.$group['id'].'-more'"
              :footer-id="$group['id'].'-more'"
              :next-page-url="$group['nextPageUrl']"
            >
              <x-slot:empty></x-slot:empty>

              @foreach ($group['rows'] as $row)
                @include('app.vaults.search._row', ['row' => $row])
              @endforeach
            </x-list-panel>
          </section>
        @endforeach
      </section>
    @endif
  </div>
</x-shell>
