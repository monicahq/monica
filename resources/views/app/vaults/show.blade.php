{{-- One vault, opened: what needs doing, the people who matter, and what happened lately. --}}
{{-- @var \App\ViewModels\Vaults\VaultDashboardViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $tile = $viewModel->tile();
  $ceiling = $viewModel->ceilingMessage();
  $favourites = $viewModel->favourites();
  $recentlyViewed = $viewModel->recentlyViewed();
  $lifeEvents = $viewModel->lifeEvents();
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
      @include('app.vaults._attention', ['attention' => $viewModel->attention()])
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

      <x-page-section :title="__('Recent life events')" icon="timeline">
        <x-panel :flush="true">
          @if ($lifeEvents === [])
            <p class="px-4 py-3 text-sm text-pretty text-muted">{{ __('What happens with the people in this vault, once you write it on their page.') }}</p>
          @else
            <ul>
              @foreach ($lifeEvents as $lifeEvent)
                <li>
                  <{{ $lifeEvent['url'] === null ? 'div' : 'a' }}
                    @if ($lifeEvent['url'] !== null) href="{{ $lifeEvent['url'] }}" data-turbo="true" @endif
                    class="{{ $row }}"
                  >
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg border {{ $lifeEvent['tint'] }}">
                      <x-icon :name="$lifeEvent['icon']" :size="18" :stroke-width="1.5" />
                    </span>

                    <span class="flex min-w-0 flex-1 flex-col">
                      <span class="flex items-baseline justify-between gap-3">
                        <span class="min-w-0 font-semibold text-pretty">{{ $lifeEvent['title'] }}</span>

                        <span class="shrink-0 text-xs text-muted">{{ $lifeEvent['date'] }}</span>
                      </span>

                      @if ($lifeEvent['who'] !== '')
                        <span class="line-clamp-2 text-sm text-pretty text-muted">{{ $lifeEvent['who'] }}</span>
                      @endif
                    </span>
                  </{{ $lifeEvent['url'] === null ? 'div' : 'a' }}>
                </li>
              @endforeach
            </ul>
          @endif
        </x-panel>
      </x-page-section>
    </div>
  </div>
</x-shell>
