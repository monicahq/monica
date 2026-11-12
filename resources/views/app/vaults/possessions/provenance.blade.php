{{-- The Provenance section of a possession: where it comes from, who has held it, how that is known. --}}
{{-- @var \App\ViewModels\Vaults\PossessionProvenanceViewModel $viewModel --}}
@php
  $page = $viewModel->page();
  $vault = $page->vault();
  $summary = $viewModel->summary();
  $counts = $viewModel->counts();
  $events = $viewModel->events();
  $formOpen = $errors->any() || $viewModel->form()['isEditing'];
  $filters = [
    'all' => __('All events'),
    'ownership' => __('Ownership'),
    'custody' => __('Custody'),
    'authenticity' => __('Authenticity'),
  ];
@endphp

<x-shell :title="__('Provenance of :name', ['name' => $page->name()])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$page->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$page->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @include('app.vaults.possessions._header', ['page' => $page])

  <div class="grid grid-cols-1 items-start gap-x-8 gap-y-6 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
    @include('app.vaults.possessions._sections', ['page' => $page, 'current' => 'provenance'])

    <section
      x-data="{ adding: @js($formOpen), filter: 'all', counts: @js($counts) }"
      aria-labelledby="provenance-title"
      class="flex min-w-0 flex-col gap-5"
    >
      <div class="flex flex-wrap items-start gap-x-4 gap-y-3">
        <div class="flex min-w-65 flex-1 flex-col gap-1">
          <div class="flex items-center gap-2">
            <h2 id="provenance-title" class="text-section-title text-ink">{{ __('Provenance') }}</h2>
            <x-help
              :label="__('Help about provenance')"
              :title="__('What belongs in provenance')"
              guide="customise.possession-provenance"
            >
              {{ __('Where the possession comes from, who has held it and how that is known. Prices, fees and taxes stay on the transactions; link an event to one and its amount is read from there.') }}
            </x-help>
          </div>
          <p class="text-sm text-pretty text-muted">{{ __('Meaningful events in ownership, custody, origin and authenticity, in the order they happened. No financial data lives here.') }}</p>
        </div>

        <x-button size="small" type="button" x-show="! adding" @click="adding = true; $nextTick(() => document.getElementById('provenance-type')?.focus())">
          <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
          {{ __('Add an event') }}
        </x-button>
      </div>

      <x-panel flush>
        <dl class="grid grid-cols-[repeat(auto-fit,minmax(11.875rem,1fr))]">
          <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Origin') }}</dt>
            <dd class="text-base font-semibold text-ink">{{ $summary['origin'] }}</dd>
            <dd class="text-xs text-muted">{{ $summary['originNote'] }}</dd>
          </div>
          <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Chain of ownership') }}</dt>
            <dd class="text-base font-semibold text-ink">{{ $summary['chain'] }}</dd>
            <dd class="text-xs text-muted">{{ $summary['chainNote'] }}</dd>
          </div>
          <div class="flex flex-col gap-0.75 px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Verified') }}</dt>
            <dd class="text-base font-semibold text-ink tabular-nums">{{ $summary['verified'] }}</dd>
            <dd class="text-xs text-muted">{{ $summary['verifiedNote'] }}</dd>
          </div>
        </dl>
      </x-panel>

      <div x-show="adding" x-cloak>
        @include('app.vaults.possessions._provenance-form', ['viewModel' => $viewModel])
      </div>

      @if ($counts['all'] === 0)
        <x-empty-state :title="__('No provenance recorded yet')">
          <x-slot:icon><x-icon name="family" :size="20" /></x-slot:icon>
          {{ __('Where it was made, who has owned it, and the certificates that prove it. Start with what you know for certain.') }}
        </x-empty-state>
      @else
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('Which events to show') }}">
          @foreach ($filters as $key => $label)
            <x-chip :count="$counts[$key]" :selected="$key === 'all'" @click="filter = '{{ $key }}'" x-effect="$el.setAttribute('aria-pressed', filter === '{{ $key }}' ? 'true' : 'false')">
              {{ $label }}
            </x-chip>
          @endforeach
          <span class="ms-auto text-xs text-muted">{{ __('Oldest first') }}</span>
        </div>

        <div x-show="filter !== 'all' && counts[filter] === 0" x-cloak>
          <x-empty-state :title="__('Nothing of this kind yet')">
            <x-slot:icon><x-icon name="family" :size="20" /></x-slot:icon>
            {{ __('Events of this kind will appear here once they are added.') }}
          </x-empty-state>
        </div>

        <ol class="flex flex-col gap-4">
          @foreach ($events as $event)
            <li
              x-data="{ confirming: false }"
              x-show="filter === 'all' || filter === @js($event['group'])"
              aria-labelledby="provenance-{{ $event['id'] }}-title"
              class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel"
            >
              <div class="flex flex-wrap items-start gap-x-4 gap-y-3 p-4">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full border border-hairline bg-card text-[#5b4192] dark:text-[#b49ae4]" aria-hidden="true">
                  <x-icon :name="$event['icon']" :size="15" />
                </span>

                <div class="flex min-w-55 flex-1 flex-col gap-1.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-ink">{{ $event['date'] }}</span>
                    <x-badge>{{ $event['type'] }}</x-badge>
                    @if ($event['isVerified'])
                      <x-badge tone="success" icon="check">{{ __('Verified') }}</x-badge>
                    @else
                      <span class="text-xs text-muted">{{ __('Not verified') }}</span>
                    @endif
                  </div>
                  <h3 id="provenance-{{ $event['id'] }}-title" class="text-base font-semibold text-ink">{{ $event['title'] }}</h3>
                  <p @class(['text-sm', 'text-body' => $event['who'] !== null, 'text-muted' => $event['who'] === null])>
                    {{ $event['who'] ?? __('No people or place recorded.') }}
                  </p>
                </div>

                <div class="flex gap-1.5">
                  <x-button.secondary size="small" :href="$event['editUrl']">
                    {{ __('Edit') }}<span class="sr-only">, {{ $event['title'] }}</span>
                  </x-button.secondary>
                  <x-button variant="ghost" size="small" type="button" @click="confirming = true; $nextTick(() => $refs.keep.focus())" ::aria-expanded="confirming ? 'true' : 'false'" aria-controls="provenance-{{ $event['id'] }}-delete">
                    {{ __('Delete') }}<span class="sr-only">, {{ $event['title'] }}</span>
                  </x-button>
                </div>
              </div>

              <div
                id="provenance-{{ $event['id'] }}-delete"
                x-show="confirming"
                x-cloak
                @keydown.escape="confirming = false"
                role="alertdialog"
                aria-labelledby="provenance-{{ $event['id'] }}-delete-title"
                aria-describedby="provenance-{{ $event['id'] }}-delete-body"
                class="flex flex-wrap items-center gap-x-4 gap-y-2.5 border-y border-error-edge bg-error-tint px-4 py-3"
              >
                <div class="min-w-60 flex-1">
                  <p id="provenance-{{ $event['id'] }}-delete-title" class="text-sm font-semibold text-ink">{{ $event['deleteTitle'] }}</p>
                  <p id="provenance-{{ $event['id'] }}-delete-body" class="text-sm text-pretty text-body">{{ $event['deleteWarning'] }}</p>
                </div>
                <x-button.secondary size="small" type="button" x-ref="keep" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>
                <x-form method="delete" :action="$event['destroyUrl']">
                  <x-button.danger size="small">{{ __('Delete') }}</x-button.danger>
                </x-form>
              </div>

              @if ($event['description'] !== null || $event['reference'] !== null || $event['sourceUrl'] !== null || $event['transaction'] !== null)
                <div class="flex flex-col gap-2.5 border-t border-hairline px-4 py-3.5">
                  @if ($event['description'] !== null)
                    <p class="max-w-reading text-sm text-pretty whitespace-pre-line text-body">{{ $event['description'] }}</p>
                  @endif

                  @if ($event['reference'] !== null || $event['sourceUrl'] !== null)
                    <div class="flex flex-wrap gap-x-5 gap-y-1.5 text-sm">
                      @if ($event['reference'] !== null)
                        <span class="flex items-center gap-1.5">
                          <span class="text-muted">{{ __('Reference') }}</span>
                          <span class="font-mono text-ink-soft">{{ $event['reference'] }}</span>
                        </span>
                      @endif
                      @if ($event['sourceUrl'] !== null)
                        <span class="flex min-w-0 items-center gap-1.5">
                          <span class="flex text-muted" aria-hidden="true"><x-icon name="link" :size="14" /></span>
                          <x-link :href="$event['sourceUrl']" :turbo="false" rel="noopener noreferrer" target="_blank" class="truncate">{{ $event['sourceUrl'] }}</x-link>
                        </span>
                      @endif
                    </div>
                  @endif

                  @if ($event['transaction'] !== null)
                    <a href="{{ $event['transaction']['url'] }}" class="focus-ring flex w-fit items-center gap-2.5 rounded-md border border-hairline py-2 ps-2 pe-3 text-sm no-underline transition-colors hover:bg-hover">
                      <span class="flex size-7 items-center justify-center rounded-md bg-card text-muted" aria-hidden="true"><x-icon name="coin" :size="14" /></span>
                      <span class="text-ink">{{ $event['transaction']['label'] }}</span>
                      <span class="font-semibold text-ink tabular-nums">{{ $event['transaction']['total'] }}</span>
                    </a>
                  @endif
                </div>
              @endif
            </li>
          @endforeach
        </ol>
      @endif
    </section>
  </div>
</x-shell>
