{{-- Everybody in one vault, on one list. --}}
{{-- @var \App\ViewModels\Vaults\ContactsViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $ceiling = $viewModel->ceilingMessage();
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
        <x-actions-menu.item :href="route('vaults.duplicates.index', $vault)" data-turbo="true">
          {{ __('Manage duplicates') }}
        </x-actions-menu.item>
      </x-actions-menu>
    </div>
  </header>

  @if ($ceiling !== null)
    <x-paywall-prompt :message="$ceiling" id="contact-ceiling" />
  @endif

  <div id="contacts" x-merge="replace" class="flex flex-col gap-3.5">
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

            <tbody id="contact-rows" x-merge="append">
              @include('app.vaults.contacts._groups', ['viewModel' => $viewModel])
            </tbody>
          </table>
        </div>

        <x-slot:footer id="pagination" class="flex flex-wrap items-center gap-3">
          @if ($viewModel->hasMorePages())
            <x-link x-target="contact-rows pagination" :href="$viewModel->nextPageUrl()">{{ __('Load more') }}</x-link>
          @else
            <span class="text-xs text-pretty text-muted">{{ $viewModel->endLine() }}</span>
          @endif

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
