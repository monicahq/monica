{{-- All possessions of a vault by location, and what the screen offers while the vault holds none. --}}
{{-- @var \App\ViewModels\Vaults\PossessionsViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $link = 'focus-ring rounded-sm text-link underline decoration-hairline-strong underline-offset-2 hover:decoration-ink';
@endphp

<x-shell :title="__('All possessions')">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('All possessions')]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  <x-page-header :title="__('All possessions')" :description="$viewModel->isEmpty() ? __('Everything you own in this vault, where it is kept and what it is worth.') : $viewModel->summary()">
    @unless ($viewModel->isEmpty())
      <x-slot:actions>
        <x-button :href="$viewModel->newUrl()">{{ __('Add a possession') }}</x-button>
      </x-slot:actions>
    @endunless
  </x-page-header>

  @if ($viewModel->isEmpty())
    <section aria-labelledby="possessions-empty-title" class="grid overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel md:grid-cols-2">
      <div class="flex flex-col justify-center gap-4 px-6 py-8 sm:px-8 sm:py-9">
        <span class="flex size-12 items-center justify-center rounded-lg border border-hairline bg-card text-body" aria-hidden="true">
          <x-icon name="key" :size="24" />
        </span>

        <div>
          <h2 id="possessions-empty-title" class="text-[22px] font-semibold tracking-[-0.02em] text-ink">{{ __('Nothing here yet') }}</h2>
          <p class="mt-2 max-w-reading-narrow text-base text-pretty text-body">{{ __('Write down the things you own: the car, the watch your grandmother left you, the camera on the top shelf. Each possession keeps where it is, what it is worth and who it came from, so you are not searching the day you need it.') }}</p>
        </div>

        <div class="mt-1 flex flex-wrap gap-2">
          <x-button :href="$viewModel->newUrl()">
            <x-icon name="plus" :size="16" />
            {{ __('Add your first possession') }}
          </x-button>
          <x-button.secondary :href="$viewModel->locationsSettingsUrl()">{{ __('Set up locations') }}</x-button.secondary>
        </div>
      </div>

      <div class="flex flex-col gap-3.5 border-hairline bg-card p-6 max-md:border-t sm:p-7 md:border-l">
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
          <h3 class="text-label text-ink">{{ __('Or start from a type') }}</h3>
          <span class="text-xs text-muted">{{ __('Each one comes with its own fields.') }}</span>
        </div>

        @if ($viewModel->types() === [])
          <p class="text-sm text-body">
            {{ __('Your account has no possession type yet.') }}
            <a href="{{ $viewModel->typesSettingsUrl() }}" class="{{ $link }}">{{ __('Manage types') }}</a>
          </p>
        @else
          <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            @foreach ($viewModel->types() as $type)
              <li>
                <a href="{{ $type['newUrl'] }}" class="focus-ring flex items-center gap-2.5 rounded-md border border-hairline bg-canvas px-3 py-2.5 no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover">
                  <span class="flex size-8 flex-none items-center justify-center rounded-md {{ $type['tint'] }}" aria-hidden="true">
                    <x-icon :name="$type['icon']" :size="16" />
                  </span>
                  <span class="flex min-w-0 flex-col">
                    <span class="truncate text-sm font-semibold text-ink">{{ $type['name'] }}</span>
                    <span class="truncate text-xs text-muted">{{ $type['fields'] }}</span>
                  </span>
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </section>

    @if ($viewModel->hasLocations())
      <section class="flex flex-wrap items-center gap-x-4 gap-y-3 rounded-lg border border-hairline px-4 py-3.5">
        <span class="flex text-[#2f7f86] dark:text-[#5fb3bb]" aria-hidden="true"><x-icon name="place" :size="18" /></span>
        <p class="min-w-60 flex-1 text-sm text-pretty text-body">
          <span class="font-semibold text-ink">{{ __('Your locations are ready.') }}</span>
          {{ $viewModel->locationsLine() }}
          {{ __('A possession can go straight into any of them.') }}
        </p>
        <a href="{{ $viewModel->locationsSettingsUrl() }}" class="{{ $link }} text-sm">{{ __('Manage locations') }}</a>
      </section>
    @endif
  @else
    @php
      $selection = $viewModel->selection();
      $featured = $viewModel->featured();
      $others = $viewModel->others();
      $parts = $viewModel->parts();
    @endphp

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
      <nav aria-label="{{ __('Locations') }}" class="flex flex-col gap-px rounded-lg border border-hairline bg-canvas p-2 shadow-panel">
        <div class="flex items-center px-2 pt-1.5 pb-2">
          <x-caption>{{ __('Locations') }}</x-caption>
          <a href="{{ $viewModel->tree()->manageUrl() }}" class="{{ $link }} ms-auto text-xs">{{ __('Manage') }}</a>
        </div>

        @foreach ($viewModel->tree()->nodes() as $node)
          <a
            href="{{ $node['url'] }}"
            @if ($node['isCurrent']) aria-current="page" @endif
            style="padding-inline-start: {{ 8 + $node['depth'] * 16 }}px"
            class="focus-ring flex min-h-8 items-center gap-2 rounded-md py-1.5 pe-2 text-nav transition duration-(--duration-fast) ease-out {{ $node['isCurrent'] ? 'bg-card font-medium text-ink shadow-nav-active' : 'text-body hover:bg-hover hover:text-ink' }}"
          >
            <span class="flex shrink-0" style="color: {{ $node['colour'] }}" aria-hidden="true">
              <x-icon :name="$node['icon']" :size="15" :stroke-width="1.5" />
            </span>
            <span class="min-w-0 flex-1 truncate">{{ $node['name'] }}</span>
            <span class="text-xs text-muted">{{ $node['count'] }}</span>
          </a>
        @endforeach
      </nav>

      <div class="flex min-w-0 flex-col gap-7">
        <section aria-labelledby="possessions-selection-title" class="flex flex-col gap-3.5 rounded-lg border border-hairline bg-canvas p-4 shadow-panel">
          <div class="flex flex-wrap items-start gap-4">
            <div class="min-w-50 flex-1">
              <h2 id="possessions-selection-title" class="text-section-title text-ink">{{ $selection['name'] }}</h2>
              @if ($selection['address'] !== '')
                <p class="mt-0.5 text-sm text-muted">{{ $selection['address'] }}</p>
              @endif
            </div>

            <dl class="flex gap-7 text-sm">
              <div>
                <dt class="text-xs text-muted">{{ __('Possessions') }}</dt>
                <dd class="mt-0.5 text-xl text-ink">{{ $selection['count'] }}</dd>
              </div>
              <div>
                <dt class="text-xs text-muted">{{ __('Declared value') }}</dt>
                <dd class="mt-0.5 text-xl text-ink">{{ $selection['value'] }}</dd>
              </div>
            </dl>
          </div>

          @if ($parts !== [])
            <div class="flex flex-col gap-2.5">
              <div class="flex h-2 gap-0.5" role="img" aria-label="{{ $viewModel->partsLabel() }}">
                @foreach ($parts as $part)
                  <div class="rounded-full" style="flex: {{ $part['count'] }}; background-color: {{ $part['colour'] }}"></div>
                @endforeach
              </div>

              <ul class="flex flex-wrap gap-x-4.5 gap-y-1.5" aria-hidden="true">
                @foreach ($parts as $part)
                  <li class="flex items-center gap-1.5 text-xs text-body">
                    <span class="size-2 rounded-full" style="background-color: {{ $part['colour'] }}"></span>
                    {{ $part['name'] }}
                    <span class="text-muted">{{ $part['count'] }}</span>
                  </li>
                @endforeach
              </ul>
            </div>
          @endif
        </section>

        @if ($featured !== [])
          <section aria-labelledby="possessions-featured-title" class="flex flex-col gap-3">
            <h2 id="possessions-featured-title" class="px-0.5 text-section-title text-ink">{{ __('Featured here') }}</h2>

            <ul class="grid grid-cols-[repeat(auto-fill,minmax(15rem,1fr))] gap-3">
              @foreach ($featured as $possession)
                <li class="relative flex gap-3 rounded-lg border border-hairline bg-canvas px-4 py-3.5 shadow-panel transition-colors duration-(--duration-fast) ease-out hover:bg-hover">
                  <span class="flex size-8 flex-none items-center justify-center rounded-md {{ $possession['tint'] }}" aria-hidden="true">
                    <x-icon :name="$possession['icon']" :size="16" />
                  </span>
                  <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <a href="{{ $possession['url'] }}" class="focus-ring rounded-sm text-sm font-semibold text-ink no-underline after:absolute after:inset-0 after:rounded-lg">{{ $possession['name'] }}</a>
                    <span class="text-sm text-muted">{{ $possession['place'] }} · {{ $possession['value'] }}</span>
                    @if ($possession['description'])
                      <span class="mt-1.5 text-sm text-body">{{ $possession['description'] }}</span>
                    @endif
                  </span>
                  <span class="flex text-[#9a6a24] dark:text-[#d9a441]">
                    <x-icon name="star-filled" :size="15" :label="__('Featured')" />
                  </span>
                </li>
              @endforeach
            </ul>
          </section>
        @endif

        <section aria-labelledby="possessions-others-title" class="flex flex-col gap-3">
          <h2 id="possessions-others-title" class="px-0.5 text-section-title text-ink">{{ $viewModel->othersTitle() }}</h2>

          <x-panel flush>
            @if ($others === [])
              <p class="px-4 py-5 text-sm text-muted">{{ __('Nothing else is kept here. Featured possessions are listed above.') }}</p>
            @else
              <ul class="divide-y divide-hairline">
                @foreach ($others as $possession)
                  <li class="relative flex items-center gap-3 px-4 py-3 transition-colors duration-(--duration-fast) ease-out hover:bg-hover">
                    <span class="flex size-8 flex-none items-center justify-center rounded-md {{ $possession['tint'] }}" aria-hidden="true">
                      <x-icon :name="$possession['icon']" :size="16" />
                    </span>
                    <span class="min-w-0 flex-1">
                      <a href="{{ $possession['url'] }}" class="focus-ring block truncate rounded-sm text-sm font-semibold text-ink no-underline after:absolute after:inset-0">{{ $possession['name'] }}</a>
                      <span class="block truncate text-sm text-muted">{{ $possession['type'] }} · {{ $possession['place'] }}</span>
                    </span>
                    @if ($possession['isLent'])
                      <x-badge>{{ __('Lent') }}</x-badge>
                    @endif
                    <span class="w-24 text-end text-sm text-body">{{ $possession['value'] }}</span>
                  </li>
                @endforeach
              </ul>
            @endif
          </x-panel>
        </section>
      </div>
    </div>
  @endif
</x-shell>
