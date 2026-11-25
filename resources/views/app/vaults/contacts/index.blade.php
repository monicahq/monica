{{-- Everybody in one vault, on one list. --}}
{{-- @var \App\ViewModels\Vaults\ContactsViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $ceiling = $viewModel->ceilingMessage();
  $categories = $viewModel->categories();
  $menuCategories = $viewModel->menuCategories();
  $categoryChange = $viewModel->categoryChange();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('People')]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="contacts" />
  </x-slot:sidebar>

  <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
    <h1 class="min-w-0 text-page-title text-ink">{{ $viewModel->title() }}</h1>

    <div class="flex items-center gap-2">
      <x-button :href="route('contacts.statistics.index', $vault)" type="button" variant="secondary" size="small" data-turbo="true" class="max-sm:hidden">
        {{ __('Statistics') }}
      </x-button>

      <x-button
        :href="$ceiling === null ? $viewModel->newContactUrl() : null"
        type="button"
        :aria-disabled="$ceiling !== null ? 'true' : null"
        :aria-describedby="$ceiling !== null ? 'contact-ceiling' : null"
        data-turbo="true"
        size="small"
      >
        <x-slot:icon>
          <x-icon name="plus" :size="16" :stroke-width="1.6" />
        </x-slot:icon>

        {{ __('Add somebody') }}
      </x-button>

      <x-actions-menu :label="__('More actions for people')">
        <x-actions-menu.item :href="route('contacts.statistics.index', $vault)" data-turbo="true" class="sm:hidden">
          {{ __('Statistics') }}
        </x-actions-menu.item>
        <x-actions-menu.item :href="route('vaults.duplicates.index', $vault)" data-turbo="true">
          {{ __('Manage duplicates') }}
        </x-actions-menu.item>
      </x-actions-menu>
    </div>
  </header>

  @if ($ceiling !== null)
    <x-paywall-prompt :message="$ceiling" id="contact-ceiling" />
  @endif

  <div
    id="contacts"
    x-merge="replace"
    x-data="contactSelection(@js(['locale' => str_replace('_', '-', app()->getLocale()), 'wordings' => $viewModel->selectionWordings(), 'nothingSelected' => __('Nothing selected')]))"
    @keydown.escape="escape()"
    class="flex flex-col gap-3.5"
  >
    <p class="sr-only" aria-live="polite" x-text="announcement"></p>

    @if ($categoryChange !== null)
      <x-notice tone="quiet" icon="check-circle">
        <span id="contact-category-change">{{ $categoryChange['line'] }} {{ $categoryChange['unchangedLine'] }}</span>

        @if ($categoryChange['line'] !== null)
          <x-slot:action>
            <x-form method="put" :action="$viewModel->categoryChangeUrl()">
              <input type="hidden" name="restores" value="1" />

              @foreach ($categoryChange['restorations'] as $index => $restoration)
                <input type="hidden" name="contacts[{{ $index }}][contact]" value="{{ $restoration['contact'] }}" />

                @foreach ($restoration['categories'] as $categoryId)
                  <input type="hidden" name="contacts[{{ $index }}][categories][]" value="{{ $categoryId }}" />
                @endforeach
              @endforeach

              <x-button.secondary size="small" aria-describedby="contact-category-change" x-init="$nextTick(() => $el.focus())">{{ __('Undo') }}</x-button.secondary>
            </x-form>
          </x-slot:action>
        @endif
      </x-notice>
    @endif

    <div x-show="count === 0" class="flex flex-col gap-3.5">
      <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
        <div class="flex flex-wrap items-center gap-2">
          @foreach ($viewModel->filters() as $filter)
            <x-chip
              :href="$filter['url']"
              :selected="$filter['isCurrent']"
              :icon="$filter['icon']"
              :count="$filter['count']"
              x-target.push="contacts"
            >{{ $filter['label'] }}</x-chip>
          @endforeach
        </div>

        <div class="ml-auto flex min-w-0 items-center justify-end gap-2">
          <x-icon-button :href="$viewModel->customiseUrl()" icon="layers" :label="__('Customise view')" align="right" data-turbo="true" />

          <form method="get" action="{{ $viewModel->searchUrl() }}" x-target.push="contacts" role="search" class="w-72 max-w-full">
            <input type="hidden" name="sort" value="{{ $viewModel->sort() }}" />
            <input type="hidden" name="direction" value="{{ $viewModel->direction() }}" />
            @if ($viewModel->size() !== $viewModel->defaultSize())
              <input type="hidden" name="size" value="{{ $viewModel->size() }}" />
            @endif
            @foreach ($viewModel->categoryIds() as $categoryId)
              <input type="hidden" name="categories[]" value="{{ $categoryId }}" />
            @endforeach
            @foreach ($viewModel->templateFilterParameters() as $parameter => $value)
              @if ($value !== null)
                <input type="hidden" name="{{ $parameter }}" value="{{ $value }}" />
              @endif
            @endforeach

            <x-input
              type="search"
              id="query"
              size="small"
              :value="$viewModel->query()"
              :placeholder="__('Search names and nicknames')"
              :aria-label="__('Search the people in this vault')"
              class="w-full"
            />
          </form>
        </div>
      </div>

      @if ($viewModel->templateChips() !== [])
        <div class="flex flex-wrap items-center gap-2" aria-label="{{ __('Filters') }}" role="group">
          @foreach ($viewModel->templateChips() as $chip)
            <x-chip :href="$chip['url']" x-target.push="contacts">
              {{ $chip['label'] }}
              <span class="sr-only">{{ __('Remove this filter') }}</span>
              <x-icon name="close" :size="12" aria-hidden="true" />
            </x-chip>
          @endforeach

          <x-link :href="$viewModel->clearFiltersUrl()" x-target.push="contacts" class="text-sm focus-ring">{{ __('Clear filters') }}</x-link>
        </div>
      @endif

      @if ($categories !== [])
        <form method="get" action="{{ $viewModel->searchUrl() }}" x-target.push="contacts" class="flex flex-wrap items-end gap-x-3 gap-y-2">
          <input type="hidden" name="sort" value="{{ $viewModel->sort() }}" />
          <input type="hidden" name="direction" value="{{ $viewModel->direction() }}" />
          @if ($viewModel->query() !== '')
            <input type="hidden" name="query" value="{{ $viewModel->query() }}" />
          @endif
          @if ($viewModel->size() !== $viewModel->defaultSize())
            <input type="hidden" name="size" value="{{ $viewModel->size() }}" />
          @endif
          @foreach ($viewModel->templateFilterParameters() as $parameter => $value)
            @if ($value !== null)
              <input type="hidden" name="{{ $parameter }}" value="{{ $value }}" />
            @endif
          @endforeach

          <fieldset class="flex min-w-0 flex-wrap items-center gap-2">
            <legend class="mb-2 text-label text-ink">{{ __('Categories') }}</legend>

            @foreach ($categories as $category)
              <x-option-row chip :id="'category-'.$category['id']" name="categories[]" :value="$category['id']" :checked="$category['isChosen']">
                {{ $category['label'] }} <span class="text-muted">{{ $category['count'] }}</span>
              </x-option-row>
            @endforeach
          </fieldset>

          <x-button type="submit" variant="secondary" size="small">{{ __('Show these people') }}</x-button>
        </form>
      @endif
    </div>

    <div
      x-cloak
      x-show="count > 0"
      role="group"
      aria-label="{{ __('Actions for the selected people') }}"
      class="sticky top-4 z-20 flex flex-wrap items-center gap-1.5 self-start rounded-lg border border-hairline bg-canvas py-1.25 pr-1.25 pl-3.5 shadow-pop motion-safe:animate-toolbar-in"
    >
      <span class="pr-2 text-sm font-semibold whitespace-nowrap text-ink tabular-nums" x-text="label"></span>

      <span class="h-5 w-px bg-hairline" aria-hidden="true"></span>

      @if ($menuCategories === [])
        <x-button type="button" variant="ghost" size="small" aria-disabled="true" aria-describedby="contact-categories-empty">
          <x-slot:icon>
            <x-icon name="tag" :size="15" :stroke-width="1.5" />
          </x-slot:icon>

          {{ __('Change category') }}
        </x-button>

        <span id="contact-categories-empty" class="text-xs text-muted">{{ __('Your list of categories is empty.') }}</span>
      @else
        <x-form method="put" :action="$viewModel->categoryChangeUrl()">
          <template x-for="(uuid, index) in selected" :key="uuid">
            <span>
              <input type="hidden" :name="`contacts[${index}][contact]`" :value="uuid" />

              <template x-if="category !== null">
                <input type="hidden" :name="`contacts[${index}][categories][]`" :value="category" />
              </template>
            </span>
          </template>

          <x-actions-menu :label="__('Change category')" :text="__('Change category')" variant="ghost" align="left">
            <x-slot:glyph>
              <x-icon name="tag" :size="15" :stroke-width="1.5" />
            </x-slot:glyph>

            <div role="group" aria-labelledby="contact-category-move-to">
              <x-caption id="contact-category-move-to" class="block px-2.5 pt-2 pb-1.5">{{ __('Move to') }}</x-caption>

              @foreach ($menuCategories as $menuCategory)
                <button type="button" role="menuitem" @click="menuOpen = false; changeCategory({{ $menuCategory['id'] }}, $el.form)" class="flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-left font-sans text-base text-ink transition-colors hover:bg-hover focus-ring-inset">
                  {{ $menuCategory['label'] }}
                </button>
              @endforeach
            </div>

            <div class="my-1 h-px bg-hairline" role="separator"></div>

            <button type="button" role="menuitem" @click="menuOpen = false; changeCategory(null, $el.form)" class="flex w-full cursor-pointer items-center gap-2.5 min-h-8 rounded-md px-2.5 text-left font-sans text-base text-muted transition-colors hover:bg-hover focus-ring-inset">
              {{ __('Remove category') }}
            </button>
          </x-actions-menu>
        </x-form>
      @endif

      <form method="get" action="{{ route('contacts.deletion.show', $vault) }}" x-target="contact-deletion">
        <template x-for="uuid in selected" :key="uuid">
          <input type="hidden" name="contacts[]" :value="uuid" />
        </template>

        <x-button type="submit" variant="ghost" tone="danger" size="small">{{ __('Delete') }}</x-button>
      </form>

      <span class="h-5 w-px bg-hairline" aria-hidden="true"></span>

      <x-icon-button icon="close" :label="__('Clear the selection')" @click="clear()" />
    </div>

    <div id="contact-deletion" role="status"></div>

    @if ($viewModel->resultLine() !== '')
      <p class="text-sm text-pretty text-muted">{{ $viewModel->resultLine() }}</p>
    @endif

    <x-panel :flush="true">
      @if ($viewModel->vaultIsEmpty())
        <x-empty-state :title="__('Nobody here yet')">
          <x-slot:icon>
            <x-icon name="people" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ $viewModel->emptyLine() }}

          <x-slot:action>
            <x-button
              :href="$ceiling === null ? $viewModel->newContactUrl() : null"
              type="button"
              :aria-disabled="$ceiling !== null ? 'true' : null"
              :aria-describedby="$ceiling !== null ? 'contact-ceiling' : null"
              data-turbo="true"
            >
              <x-slot:icon>
                <x-icon name="plus" :size="18" :stroke-width="1.6" />
              </x-slot:icon>

              {{ __('Add somebody') }}
            </x-button>
          </x-slot:action>
        </x-empty-state>
      @elseif ($viewModel->noResults())
        <div class="flex min-h-60 flex-col items-center justify-center gap-3.5 px-8 py-12 text-center">
          <p class="text-section-title text-ink">{{ __('Nothing matches') }}</p>

          <p class="max-w-reading-narrow text-base text-pretty text-muted">{{ __('Try a shorter search, or another filter above.') }}</p>
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full min-w-190 border-collapse text-left">
            <caption class="sr-only">{{ __('People') }}</caption>

            <thead>
              <tr class="border-b border-hairline">
                <th scope="col" class="w-11 py-3 pl-4">
                  <x-selection-checkbox
                    :label="__('Select everybody on this page')"
                    data-select-all
                    ::checked="allChecked"
                    x-effect="$el.indeterminate = someChecked"
                    @change="toggleAll()"
                  />
                </th>

                @foreach ($viewModel->headings() as $heading)
                  <th scope="col" @if ($heading['ariaSort'] !== null) aria-sort="{{ $heading['ariaSort'] }}" @endif class="px-4 py-3 text-xs font-medium whitespace-nowrap first:pl-4">
                    @if ($heading['url'] !== null)
                      <a
                        href="{{ $heading['url'] }}"
                        x-target.push="contacts"
                        @class([
                          '-mx-1.5 inline-flex items-center gap-1 rounded-xs px-1.5 py-0.5 no-underline focus-ring-inset',
                          'text-ink' => $heading['ariaSort'] !== null,
                          'text-muted hover:text-ink' => $heading['ariaSort'] === null,
                        ])
                      >
                        {{ $heading['label'] }}

                        @if ($heading['ariaSort'] !== null)
                          <x-icon :name="$heading['ariaSort'] === 'ascending' ? 'chevron-down' : 'chevron-up'" :size="14" :stroke-width="1.4" />
                        @endif
                      </a>
                    @else
                      <span class="text-muted">{{ $heading['label'] }}</span>
                    @endif
                  </th>
                @endforeach
              </tr>
            </thead>

            <tbody id="contact-rows">
              @include('app.vaults.contacts._groups', ['viewModel' => $viewModel])
            </tbody>
          </table>
        </div>

        <x-slot:footer id="pagination" class="flex flex-wrap items-center gap-3">
          <x-pagination :paginator="$viewModel->contacts()" :size="$viewModel->size()" :default="$viewModel->defaultSize()" target="contacts" class="grow" />

          <x-button.secondary
            :href="$ceiling === null ? $viewModel->newContactUrl() : null"
            type="button"
            :aria-disabled="$ceiling !== null ? 'true' : null"
            :aria-describedby="$ceiling !== null ? 'contact-ceiling' : null"
            data-turbo="true"
            size="small"
            class="ml-auto"
          >
            <x-slot:icon>
              <x-icon name="plus" :size="16" :stroke-width="1.6" />
            </x-slot:icon>

            {{ __('Add somebody') }}
          </x-button.secondary>
        </x-slot:footer>
      @endif
    </x-panel>
  </div>
</x-shell>
