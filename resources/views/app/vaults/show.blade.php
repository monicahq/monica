{{-- One vault, opened: what happened lately, what needs doing, and the people who matter, one tab each. --}}
{{-- @var \App\ViewModels\Vaults\VaultDashboardViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $tile = $viewModel->tile();
  $ceiling = $viewModel->ceilingMessage();
  $favourites = $viewModel->favourites();
  $recentlyViewed = $viewModel->recentlyViewed();
  $lifeEventForm = $viewModel->lifeEventForm();
  $row = 'flex items-center gap-3 border-b border-hairline-soft px-4 py-2.5 text-base text-ink no-underline transition-colors duration-(--duration-fast) last:border-b-0 hover:bg-hover focus-ring-inset';
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="dashboard" />
  </x-slot:sidebar>

  <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
    <div class="flex min-w-0 flex-col gap-3">
      <div class="flex items-center gap-3">
        <span
          aria-hidden="true"
          class="flex size-9 shrink-0 items-center justify-center rounded-lg font-semibold"
          style="color: {{ $tile['colour'] }}; background: {{ $tile['tint'] }}"
        >{{ $tile['initial'] }}</span>

        <h1 class="text-page-title text-balance text-ink">{{ $vault->name }}</h1>
      </div>

      @if ($viewModel->description())
        <p class="max-w-prose text-base text-pretty text-body">{{ $viewModel->description() }}</p>
      @endif

      <ul class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-body">
        @foreach ($viewModel->figures() as $figure)
          <li>{{ $figure }}</li>
        @endforeach
      </ul>
    </div>

    <x-button
      :href="$ceiling === null ? $viewModel->newContactUrl() : null"
      type="button"
      :aria-disabled="$ceiling !== null ? 'true' : null"
      :aria-describedby="$ceiling !== null ? 'contact-ceiling' : null"
      data-turbo="true"
      size="small"
      class="max-sm:w-full"
    >
      <x-slot:icon>
        <x-icon name="plus" :size="16" :stroke-width="1.6" />
      </x-slot:icon>

      {{ __('Add somebody') }}
    </x-button>
  </header>

  @if ($ceiling !== null)
    <x-paywall-prompt :message="$ceiling" id="contact-ceiling" />
  @endif

  <section id="contact-timeline" x-merge="replace" x-data="dashboardTabs" class="flex flex-col gap-5">
    <div
      @if ($lifeEventForm !== null)
        x-data="lifeEventPanel(@js($lifeEventForm->projection()))"
        data-life-event-panel
      @endif
      class="flex flex-col gap-5"
    >
      <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-3">
        <x-tabs
          :aria-label="__('What to show')"
          x-on:keydown.right.prevent="selectAdjacentTab($el, 1)"
          x-on:keydown.left.prevent="selectAdjacentTab($el, -1)"
          x-on:keydown.home.prevent="selectFirstTab($el)"
          x-on:keydown.end.prevent="selectLastTab($el)"
          class="min-w-0 grow"
        >
          @foreach ($viewModel->tabs() as $tab)
            <x-tabs.item
              :icon="$tab['icon']"
              :count="$tab['count']"
              :selected="$loop->first"
              id="dashboard-tab-{{ $tab['key'] }}"
              data-tab="{{ $tab['key'] }}"
              aria-controls="dashboard-panel-{{ $tab['key'] }}"
              tabindex="{{ $loop->first ? '0' : '-1' }}"
              x-bind:aria-selected="view === '{{ $tab['key'] }}' ? 'true' : 'false'"
              x-bind:tabindex="view === '{{ $tab['key'] }}' ? 0 : -1"
              x-on:click="view = '{{ $tab['key'] }}'"
            >{{ $tab['label'] }}</x-tabs.item>
          @endforeach
        </x-tabs>

        @if ($lifeEventForm !== null)
          <x-button.secondary
            type="button"
            x-show="view === 'timeline'"
            x-ref="addButton"
            x-on:click="show()"
            x-bind:aria-expanded="open ? 'true' : 'false'"
            :aria-controls="$lifeEventForm->isRendered() ? 'life-event-panel' : null"
            x-bind:disabled="open"
            class="max-sm:w-full"
          >
            <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
            {{ __('Add a life event') }}
          </x-button.secondary>
        @endif

        <x-link x-show="view === 'attention'" x-cloak :href="$viewModel->attention()->remindersUrl()" :turbo="true" class="text-sm">{{ __('All reminders') }}</x-link>
      </div>

      <div role="tabpanel" id="dashboard-panel-timeline" aria-labelledby="dashboard-tab-timeline" x-show="view === 'timeline'" class="flex flex-col gap-5">
        @if ($lifeEventForm?->isRendered())
          @include('app.vaults.contacts._life-event-panel', ['form' => $lifeEventForm])
        @endif

        @include('app.vaults._timeline', ['timeline' => $viewModel->timeline()])
      </div>

      <div role="tabpanel" id="dashboard-panel-attention" aria-labelledby="dashboard-tab-attention" x-show="view === 'attention'" x-cloak>
        <h2 class="sr-only">{{ __('Needs your attention') }}</h2>

        @include('app.vaults._attention-dates', ['attention' => $viewModel->attention(), 'showsAllReminders' => false])
      </div>

      <div role="tabpanel" id="dashboard-panel-favourites" aria-labelledby="dashboard-tab-favourites" x-show="view === 'favourites'" x-cloak class="flex flex-col gap-3">
        <h2 class="sr-only">{{ __('Favourite people') }}</h2>

        <p class="text-sm text-pretty text-muted">{{ __('People you marked as a favourite on their page, one step away.') }}</p>

        <x-panel :flush="true">
          @if ($favourites === [])
            <x-empty-state :title="__('Nobody here yet')">
              {{ __('Mark somebody as a favourite on their page and they stay here, one step away.') }}
            </x-empty-state>
          @else
            <ul>
              @foreach ($favourites as $person)
                <li>
                  <a href="{{ $person['url'] }}" data-turbo="true" class="{{ $row }}">
                    <x-contact-avatar :name="$person['name']" :url="$person['avatarUrl']" :size="32" alt="" />

                    <span class="min-w-0 flex-1 truncate font-semibold">{{ $person['name'] }}</span>

                    <x-icon name="star-filled" :size="16" class="shrink-0 text-muted" />
                  </a>
                </li>
              @endforeach
            </ul>
          @endif

          @if ($viewModel->moreFavouritesUrl() !== null)
            <x-slot:footer>
              <x-link :href="$viewModel->moreFavouritesUrl()" :turbo="true">{{ __('All favourites') }}</x-link>
            </x-slot:footer>
          @endif
        </x-panel>
      </div>

      <div role="tabpanel" id="dashboard-panel-recent" aria-labelledby="dashboard-tab-recent" x-show="view === 'recent'" x-cloak class="flex flex-col gap-3">
        <h2 class="sr-only">{{ __('Recently viewed') }}</h2>

        <p class="text-sm text-pretty text-muted">{{ __('The last people whose page you opened in this vault.') }}</p>

        <x-panel :flush="true">
          @if ($recentlyViewed === [])
            <x-empty-state :title="__('Nobody opened yet')">
              {{ __('The people you look up appear here, the latest first.') }}
            </x-empty-state>
          @else
            <ul>
              @foreach ($recentlyViewed as $person)
                <li>
                  <a href="{{ $person['url'] }}" data-turbo="true" class="{{ $row }}">
                    <x-contact-avatar :name="$person['name']" :url="$person['avatarUrl']" :size="32" alt="" />

                    <span class="min-w-0 flex-1 truncate font-semibold">{{ $person['name'] }}</span>

                    <span class="shrink-0 text-sm text-muted">{{ $person['when'] }}</span>
                  </a>
                </li>
              @endforeach
            </ul>
          @endif
        </x-panel>
      </div>
    </div>
  </section>
</x-shell>
