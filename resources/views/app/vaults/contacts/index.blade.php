{{--
  Everybody in one vault, on one list.

  The list is rows in a panel rather than a table. There are three things worth
  putting beside a name and a table of three columns is a list with lines drawn
  on it. In name order the rows are grouped under the letter they begin with,
  which is what makes a long list something to be scanned.

  Searching, narrowing and ordering all go through the URL and redraw the region
  below the header over ajax, so a narrowed list comes back the same after a
  reload and the back button undoes what the last chip did.

  Neither form submits on its own. A select that reloads the list the moment it
  is touched changes the screen under somebody's feet, which is the one thing
  this product never does, so both of them have a button.

  A name is not a link. The page of one contact is a later screen, and a link
  that leads nowhere is worse than a word that does not move.

  @var \App\ViewModels\Vaults\ContactsViewModel $viewModel
--}}
@php
  $vault = $viewModel->vault();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:bar>
    <x-vault.bar
      :vault="$vault"
      :items="[['label' => __('People'), 'icon' => 'people', 'count' => $viewModel->headcount()]]"
    />
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" :vault="$vault" current="people" />
  </x-slot:dock>

  <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
    <div class="flex min-w-0 flex-col gap-2">
      <h1 class="erica-page-title">{{ $viewModel->title() }}</h1>

      <p class="erica-lede max-w-[64ch]">{{ $viewModel->description() }}</p>
    </div>

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

        <label for="sort" class="erica-caption">{{ __('Sorted by') }}</label>

        <select id="sort" name="sort" class="erica-control erica-control--sm erica-control--select w-38">
          @foreach ($viewModel->sorts() as $sort)
            <option value="{{ $sort['value'] }}" @selected($sort['isCurrent'])>{{ $sort['label'] }}</option>
          @endforeach
        </select>

        <select id="direction" name="direction" aria-label="{{ __('Which way round the order runs') }}" class="erica-control erica-control--sm erica-control--select w-36">
          @foreach ($viewModel->directions() as $direction)
            <option value="{{ $direction['value'] }}" @selected($direction['isCurrent'])>{{ $direction['label'] }}</option>
          @endforeach
        </select>

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

      <p class="erica-small sm:ml-auto">{{ $viewModel->resultLine() }}</p>
    </div>

    <x-panel :flush="true">
      @if ($viewModel->vaultIsEmpty())
        <x-empty-state :title="__('Nobody here yet')" hue="var(--indigo)" tint="var(--indigo-tint)">
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
        <div class="erica-empty">
          <p class="erica-empty__title">{{ __('Nothing matches') }}</p>

          <p class="erica-empty__text">{{ __('Try a shorter search, or another chip above.') }}</p>
        </div>
      @else
        <div id="contact-rows" x-merge="append">
          @include('app.vaults.contacts._groups')
        </div>

        <x-slot:footer id="pagination" class="flex flex-wrap items-center gap-3">
          @if ($viewModel->hasMorePages())
            <x-link x-target="contact-rows pagination" :href="$viewModel->nextPageUrl()">{{ __('Load more') }}</x-link>
          @else
            <span class="erica-caption">{{ $viewModel->endLine() }}</span>
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
