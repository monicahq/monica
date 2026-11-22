{{-- One vault, opened: what needs doing, the people who matter, and what happened lately. --}}
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
<x-shell :title="$viewModel->title()" wide>
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

  <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
    <div class="flex min-w-0 flex-col gap-8">
      @if ($lifeEventForm !== null)
        <section
          id="contact-timeline"
          x-merge="replace"
          x-data="lifeEventPanel(@js($lifeEventForm->projection()))"
          data-life-event-panel
          class="flex flex-col gap-5"
        >
          <div class="flex flex-col gap-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <div
                role="tablist"
                aria-label="{{ __('What to show') }}"
                x-on:keydown.right.prevent="view = view === 'timeline' ? 'attention' : 'timeline'; $nextTick(() => document.getElementById(`dashboard-tab-${view}`)?.focus())"
                x-on:keydown.left.prevent="view = view === 'timeline' ? 'attention' : 'timeline'; $nextTick(() => document.getElementById(`dashboard-tab-${view}`)?.focus())"
                class="inline-flex gap-0.5 rounded-lg border border-hairline bg-card p-0.75"
              >
                @foreach ([
                  'timeline' => ['label' => __('Timeline'), 'icon' => 'timeline', 'count' => $viewModel->timelineCount()],
                  'attention' => ['label' => __('Needs your attention'), 'icon' => 'reminder', 'count' => $viewModel->attention()->count()],
                ] as $view => $tab)
                  <button
                    type="button"
                    role="tab"
                    id="dashboard-tab-{{ $view }}"
                    aria-controls="dashboard-panel-{{ $view }}"
                    x-bind:aria-selected="view === '{{ $view }}' ? 'true' : 'false'"
                    x-bind:tabindex="view === '{{ $view }}' ? 0 : -1"
                    x-on:click="view = '{{ $view }}'"
                    class="focus-ring flex h-8 cursor-pointer items-center gap-2 rounded-md px-3 text-sm font-medium text-body transition-colors duration-(--duration-fast) hover:text-ink aria-selected:bg-canvas aria-selected:text-ink aria-selected:shadow-nav-active"
                  >
                    <x-icon :name="$tab['icon']" :size="16" />
                    {{ $tab['label'] }}
                    <span class="flex h-4.75 min-w-4.75 items-center justify-center rounded-full px-1.25 text-2xs font-semibold text-muted tabular-nums">{{ $tab['count'] }}</span>
                  </button>
                @endforeach
              </div>

              <x-button.secondary
                type="button"
                x-show="view === 'timeline'"
                x-ref="addButton"
                x-on:click="show()"
                x-bind:aria-expanded="open ? 'true' : 'false'"
                :aria-controls="$lifeEventForm->isRendered() ? 'life-event-panel' : null"
                x-bind:disabled="open"
              >
                <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
                {{ __('Add a life event') }}
              </x-button.secondary>

              <x-link x-show="view === 'attention'" x-cloak :href="$viewModel->attention()->remindersUrl()" :turbo="true" class="text-sm">{{ __('All reminders') }}</x-link>
            </div>

            <div role="tabpanel" id="dashboard-panel-timeline" aria-labelledby="dashboard-tab-timeline" x-show="view === 'timeline'" class="flex flex-col gap-5">
              @if ($lifeEventForm->isRendered())
                @include('app.vaults.contacts._life-event-panel', ['form' => $lifeEventForm])
              @endif

              @include('app.vaults._timeline', ['timeline' => $viewModel->timeline()])
            </div>

            <div role="tabpanel" id="dashboard-panel-attention" aria-labelledby="dashboard-tab-attention" x-show="view === 'attention'" x-cloak>
              @include('app.vaults._attention-dates', ['attention' => $viewModel->attention(), 'showsAllReminders' => false])
            </div>
          </div>
        </section>
      @else
        @include('app.vaults._attention', ['attention' => $viewModel->attention()])

        @include('app.vaults._timeline', ['timeline' => $viewModel->timeline()])
      @endif
    </div>

    <div class="flex min-w-0 flex-col gap-8">
      <x-page-section :title="__('Favourite people')" icon="star">
        <x-panel :flush="true">
          @if ($favourites === [])
            <p class="px-4 py-3 text-sm text-pretty text-muted">{{ __('Mark somebody as a favourite on their page and they stay here, one step away.') }}</p>
          @else
            <ul>
              @foreach ($favourites as $person)
                <li>
                  <a href="{{ $person['url'] }}" data-turbo="true" class="{{ $row }}">
                    <x-contact-avatar :name="$person['name']" :url="$person['avatarUrl']" :size="32" />

                    <span class="min-w-0 truncate font-medium">{{ $person['name'] }}</span>
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
      </x-page-section>

      <x-page-section :title="__('Recently viewed')" icon="clock">
        <x-panel :flush="true">
          @if ($recentlyViewed === [])
            <p class="px-4 py-3 text-sm text-pretty text-muted">{{ __('The people you look up appear here, the latest first.') }}</p>
          @else
            <ul>
              @foreach ($recentlyViewed as $person)
                <li>
                  <a href="{{ $person['url'] }}" data-turbo="true" class="{{ $row }}">
                    <x-contact-avatar :name="$person['name']" :url="$person['avatarUrl']" :size="28" />

                    <span class="min-w-0 flex-1 truncate font-medium">{{ $person['name'] }}</span>

                    <span class="shrink-0 text-sm text-muted">{{ $person['when'] }}</span>
                  </a>
                </li>
              @endforeach
            </ul>
          @endif
        </x-panel>
      </x-page-section>
    </div>
  </div>
</x-shell>
