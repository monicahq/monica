{{--
  The front of the marketplace: everything published, and the handful of ways of
  narrowing it down to the one thing somebody came for.

  It reads signed out. Nothing here is private, and a link to the catalogue has to
  open for somebody who has never signed up.

  Everything that narrows the list sits inside `#listings` and aims at it, so each
  chip, each category and each select is a real address the back button can return
  to. The grid inside it appends rather than replaces, which is what lets the
  button at the foot add a page without throwing away the one already read.

  The two selects sit in the results header but belong to the form at the top of
  the region, which is what the `form` attribute is for. One control that narrows
  the list and one button that applies it beats a button beside every facet.

  @var \App\ViewModels\Marketplace\MarketplaceViewModel $viewModel
--}}
<x-marketplace-layout :title="__('Marketplace')">
  <header class="flex flex-col gap-2.5">
    <h1 class="text-page-title text-ink">{{ __('Marketplace') }}</h1>

    <p class="max-w-[62ch] text-base text-pretty text-body">
      {{ __('Discover templates and sections built by the community. Everything you install becomes your own private, editable copy.') }}
    </p>

    <p class="mt-1 flex w-fit items-center gap-2.5 rounded-md border border-hairline border-l-2 border-l-ink bg-canvas px-3.5 py-2.5 text-sm font-semibold text-ink">
      <x-icon name="lock" :size="17" :stroke-width="1.5" class="shrink-0 text-body" />

      {{ __('Install a private copy. Change it however you want. Updates are always optional.') }}
    </p>
  </header>

  @include('app.marketplace._statistics', ['statistics' => $viewModel->statistics()])

  <div id="listings" x-merge="replace" class="flex flex-col gap-8">
    <section class="flex flex-col gap-3.5">
      <form
        id="marketplace-filters"
        method="get"
        action="{{ route('marketplace.index') }}"
        x-target="listings"
        class="flex items-end gap-2.5"
      >
        <div class="flex-1">
          <x-input
            id="query"
            name="query"
            :value="$viewModel->query()"
            :placeholder="__('Search templates and sections')"
            :aria-label="__('Search the catalogue')"
          />
        </div>

        <x-button.secondary>{{ __('Search') }}</x-button.secondary>
      </form>

      <div class="flex flex-wrap items-center gap-2">
        @foreach ($viewModel->types() as $chip)
          <x-chip
            :href="$chip['href']"
            :selected="$chip['isCurrent']"
            :icon="$chip['icon']"
            :count="$chip['count']"
            x-target="listings"
          >
            {{ $chip['label'] }}
          </x-chip>
        @endforeach
      </div>
    </section>

    <section class="flex flex-col gap-3">
      <div class="flex items-baseline gap-2.5">
        <h2 class="text-section-title text-ink">{{ __('Categories') }}</h2>

        <span class="text-xs text-muted">{{ __('Ours to maintain, and nothing is filed twice') }}</span>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($viewModel->categories() as $category)
          <a
            href="{{ $category['href'] }}"
            x-target="listings"
            @if ($category['isCurrent']) aria-current="true" @endif
            @class([
              'flex items-start gap-3 rounded-lg border p-3.5 no-underline ',
              'transition-[background-color,border-color,transform] duration-(--duration-fast) ease-out',
              'hover:bg-hover ',
              'focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none',
              'border-ink bg-card' => $category['isCurrent'],
              'border-hairline bg-canvas' => ! $category['isCurrent'],
            ])
          >
            <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-card text-muted">
              <x-icon :name="$category['icon']" :size="18" :stroke-width="1.5" />
            </span>

            <span class="flex min-w-0 flex-col gap-0.5">
              <span class="text-sm font-semibold text-ink">{{ $category['name'] }}</span>
              <span class="text-xs text-pretty text-muted">{{ $category['description'] }}</span>
              <span class="mt-0.5 text-xs text-ink-soft">{{ $category['countLabel'] }}</span>
            </span>
          </a>
        @endforeach
      </div>
    </section>

    <section class="flex flex-col gap-3.5">
      <div class="flex flex-wrap items-end gap-x-4 gap-y-3">
        <div>
          <h2 class="text-section-title text-ink">{{ $viewModel->resultsTitle() }}</h2>

          <p class="mt-0.5 text-xs text-muted">{{ $viewModel->resultsDescription() }}</p>
        </div>

        <div class="ml-auto flex flex-wrap items-end gap-2.5">
          <x-select
            id="locale"
            name="locale"
            form="marketplace-filters"
            size="small"
            :label="__('Language')"
            class="w-40"
          >
            <option value="">{{ __('Any language') }}</option>

            @foreach ($viewModel->locales() as $option)
              <option value="{{ $option['value'] }}" @selected($option['value'] === $viewModel->locale())>{{ $option['label'] }}</option>
            @endforeach
          </x-select>

          <x-select
            id="sort"
            name="sort"
            form="marketplace-filters"
            size="small"
            :label="__('Sort')"
            class="w-44"
          >
            @foreach ($viewModel->sorts() as $option)
              <option value="{{ $option['value'] }}" @selected($option['value'] === $viewModel->sort())>{{ $option['label'] }}</option>
            @endforeach
          </x-select>

          <x-button.secondary form="marketplace-filters" size="small">{{ __('Apply') }}</x-button.secondary>
        </div>
      </div>

      {{-- The type and the category travel with the form, so narrowing by one facet keeps the others. --}}
      <input type="hidden" name="type" form="marketplace-filters" value="{{ $viewModel->type() }}" />
      <input type="hidden" name="category" form="marketplace-filters" value="{{ $viewModel->category() }}" />

      @if (count($viewModel->listings()) === 0)
        <x-panel :flush="true">
          <x-empty-state
            :title="$viewModel->isCatalogueEmpty() ? __('The catalogue is empty') : __('Nothing matches')"
          >
            <x-slot:icon><x-icon name="gift" :size="30" :stroke-width="1.4" /></x-slot:icon>

            @if ($viewModel->isCatalogueEmpty())
              {{ __('Nothing has been published yet. The templates and sections people build here land in this catalogue once they are shared and moderated.') }}
            @else
              {{ __('Nothing published answers to all of those at once. Widen the search, or drop one of the filters.') }}
            @endif
          </x-empty-state>
        </x-panel>
      @else
        <div id="listings-grid" x-merge="append" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          @foreach ($viewModel->listings() as $listing)
            @include('app.marketplace._card', ['listing' => $listing])
          @endforeach
        </div>

        <div id="listings-pager" class="flex justify-center pt-1.5">
          @if ($viewModel->hasMore())
            <x-button.secondary :href="$viewModel->nextPageUrl()" x-target="listings-grid listings-pager">
              {{ $viewModel->showMoreLabel() }}

              <x-icon name="chevron-down" :size="16" :stroke-width="1.5" />
            </x-button.secondary>
          @endif
        </div>
      @endif
    </section>
  </div>

  <section class="flex flex-col items-start gap-3.5 rounded-lg border border-hairline bg-canvas px-5 py-4.5 sm:flex-row sm:items-start">
    <span class="flex size-8 shrink-0 items-center justify-center rounded-sm bg-card text-muted" aria-hidden="true">
      <x-icon name="plus" :size="18" :stroke-width="1.6" />
    </span>

    <div class="flex min-w-0 flex-col gap-0.5">
      <p class="text-sm font-semibold text-ink">{{ __('Built something worth sharing?') }}</p>

      <p class="max-w-[66ch] text-sm text-pretty text-body">
        {{ __('Open a template or a section and choose to share it on the marketplace. You keep your own copy, and other people get theirs.') }}
      </p>
    </div>

    <div class="shrink-0 sm:ml-auto">
      @auth
        <x-button.secondary :href="route('settings.templates.index')" data-turbo="true" size="small">
          {{ __('Your templates') }}
        </x-button.secondary>
      @else
        <x-button.secondary :href="route('auth.register.new')" size="small">
          {{ __('Create an account') }}
        </x-button.secondary>
      @endauth
    </div>
  </section>
</x-marketplace-layout>
