{{-- The Valuations section of a possession: what it is worth now, how that moved, and every valuation. --}}
{{-- @var \App\ViewModels\Vaults\PossessionValuationViewModel $viewModel --}}
@php
  $page = $viewModel->page();
  $vault = $page->vault();
  $summary = $viewModel->summary();
  $valuations = $viewModel->valuations();
@endphp

<x-shell :title="__('Valuations of :name', ['name' => $page->name()])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$page->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$page->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @include('app.vaults.possessions._header', ['page' => $page])

  <div class="grid grid-cols-1 items-start gap-x-8 gap-y-6 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
    @include('app.vaults.possessions._sections', ['page' => $page, 'current' => 'valuations'])

    <section
      x-data="{ adding: @js($errors->any()) }"
      x-init="if (window.location.hash === '#new-valuation') { adding = true; $nextTick(() => document.getElementById('val-amount')?.focus()) }"
      @hashchange.window="if (window.location.hash === '#new-valuation') { adding = true; $nextTick(() => document.getElementById('val-amount')?.focus()) }"
      aria-labelledby="valuations-title"
      class="flex min-w-0 flex-col gap-5"
    >
      <div class="flex flex-wrap items-start gap-x-4 gap-y-3">
        <div class="flex min-w-65 flex-1 flex-col gap-1">
          <div class="flex items-center gap-2">
            <h2 id="valuations-title" class="text-section-title text-ink">{{ __('Valuations') }}</h2>
            <x-help
              :label="__('Help about valuations')"
              :title="__('Why valuations are never edited')"
              guide="customise.possession-valuations"
            >
              {{ __('Each estimate is kept as it was recorded, so you can see how the value moved over the years. To correct one, record a new valuation. The latest one by date is the current value, and the overview and insurance read it from here.') }}
            </x-help>
          </div>
          <p class="text-sm text-pretty text-muted">{{ __('Estimates of what this possession is worth, kept in order. The latest is shown as the current value.') }}</p>
        </div>

        <x-button size="small" type="button" x-show="! adding" @click="adding = true; $nextTick(() => document.getElementById('val-amount')?.focus())">
          <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
          {{ __('Record a valuation') }}
        </x-button>
      </div>

      <x-panel flush>
        <dl class="grid grid-cols-[repeat(auto-fit,minmax(11.875rem,1fr))]">
          <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Current value') }}</dt>
            <dd class="text-[20px] font-semibold tracking-[-0.02em] text-ink tabular-nums">{{ $summary['current'] }}</dd>
            <dd class="text-xs text-muted">{{ $summary['currentNote'] }}</dd>
          </div>
          <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Change from the previous') }}</dt>
            <dd class="text-[20px] font-semibold tracking-[-0.02em] text-ink tabular-nums">{{ $summary['change'] }}</dd>
            <dd class="text-xs text-muted">{{ $summary['changeNote'] }}</dd>
          </div>
          <div class="flex flex-col gap-0.75 px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Insured for') }}</dt>
            <dd class="text-[20px] font-semibold tracking-[-0.02em] text-ink tabular-nums">{{ $summary['insured'] }}</dd>
            @if ($summary['isUnderInsured'])
              <dd class="flex items-center gap-1 text-xs text-warning">
                <span class="flex" aria-hidden="true"><x-icon name="info" :size="13" /></span>
                {{ $summary['insuredNote'] }}
              </dd>
            @else
              <dd class="text-xs text-muted">{{ $summary['insuredNote'] }}</dd>
            @endif
          </div>
        </dl>
      </x-panel>

      <div id="new-valuation" x-show="adding" x-cloak>
        @include('app.vaults.possessions._valuation-form', ['viewModel' => $viewModel])
      </div>

      @if ($valuations === [])
        <x-empty-state :title="__('No valuation yet')">
          <x-slot:icon><x-icon name="activity" :size="20" /></x-slot:icon>
          {{ __('Record what it is worth, from your own estimate to a written appraisal. The latest one becomes its current value.') }}
        </x-empty-state>
      @else
        <div class="flex flex-col gap-3">
          <div class="flex items-baseline gap-2">
            <h3 class="text-card-title text-ink">{{ __('History') }}</h3>
            <span class="ms-auto text-xs text-muted">{{ __('Newest first') }}</span>
          </div>

          <ol class="flex flex-col gap-4">
            @foreach ($valuations as $valuation)
              <li
                x-data="{ confirming: false }"
                aria-labelledby="valuation-{{ $valuation['id'] }}-title"
                class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel"
              >
                <div class="flex flex-wrap items-start gap-x-4 gap-y-3 p-4">
                  <div class="flex min-w-55 flex-1 flex-col gap-1.5">
                    <div class="flex flex-wrap items-center gap-2">
                      <h4 id="valuation-{{ $valuation['id'] }}-title" class="flex flex-wrap items-center gap-2">
                        <x-badge tone="neutral" :icon="$valuation['icon']">{{ $valuation['type'] }}</x-badge>
                        <span class="text-sm text-muted">{{ $valuation['date'] }}</span>
                      </h4>
                      @if ($valuation['isCurrent'])
                        <x-badge tone="success" icon="check">{{ __('Current value') }}</x-badge>
                      @endif
                    </div>
                    <span @class(['text-base', 'text-ink' => $valuation['valuer'] !== null, 'text-muted' => $valuation['valuer'] === null])>
                      {{ $valuation['valuer'] ?? __('No valuer recorded') }}
                    </span>
                  </div>

                  <div class="flex flex-col gap-0.5 text-end">
                    <span class="text-[18px] font-semibold tracking-[-0.01em] text-ink tabular-nums">{{ $valuation['amount'] }}</span>
                    <span class="text-xs text-muted">{{ $valuation['change'] }}</span>
                  </div>

                  <x-button variant="ghost" size="small" type="button" @click="confirming = true; $nextTick(() => $refs.keep.focus())" ::aria-expanded="confirming ? 'true' : 'false'" aria-controls="valuation-{{ $valuation['id'] }}-delete">
                    {{ __('Delete') }}<span class="sr-only">, {{ $valuation['type'] }}, {{ $valuation['date'] }}</span>
                  </x-button>
                </div>

                <div
                  id="valuation-{{ $valuation['id'] }}-delete"
                  x-show="confirming"
                  x-cloak
                  @keydown.escape="confirming = false"
                  role="alertdialog"
                  aria-labelledby="valuation-{{ $valuation['id'] }}-delete-title"
                  aria-describedby="valuation-{{ $valuation['id'] }}-delete-body"
                  class="flex flex-wrap items-center gap-x-4 gap-y-2.5 border-y border-error-edge bg-error-tint px-4 py-3"
                >
                  <div class="min-w-60 flex-1">
                    <p id="valuation-{{ $valuation['id'] }}-delete-title" class="text-sm font-semibold text-ink">{{ $valuation['deleteTitle'] }}</p>
                    <p id="valuation-{{ $valuation['id'] }}-delete-body" class="text-sm text-pretty text-body">{{ $valuation['deleteWarning'] }}</p>
                  </div>
                  <x-button.secondary size="small" type="button" x-ref="keep" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>
                  <x-form method="delete" :action="$valuation['destroyUrl']">
                    <x-button.danger size="small">{{ __('Delete') }}</x-button.danger>
                  </x-form>
                </div>

                <dl class="grid grid-cols-[repeat(auto-fit,minmax(9.375rem,1fr))] border-t border-hairline">
                  @foreach ($valuation['parts'] as $part)
                    <div class="flex min-w-0 flex-col gap-0.75 border-e border-hairline-soft px-4 py-3">
                      <dt class="text-xs text-muted">{{ $part['label'] }}</dt>
                      <dd @class(['text-sm wrap-anywhere', 'text-ink' => $part['isSet'], 'text-muted' => ! $part['isSet'], 'font-mono' => $part['isCode'] && $part['isSet']])>
                        @if ($part['isLink'])
                          <x-link :href="$part['value']" :turbo="false" target="_blank" rel="noopener">{{ $part['value'] }}</x-link>
                        @else
                          {{ $part['value'] }}
                        @endif
                      </dd>
                    </div>
                  @endforeach
                </dl>

                <div class="flex flex-col gap-2.5 border-t border-hairline px-4 py-3.5">
                  @if ($valuation['note'] !== null)
                    <p class="max-w-reading text-sm text-pretty whitespace-pre-line text-body">{{ $valuation['note'] }}</p>
                  @else
                    <p class="text-sm text-muted">{{ __('No note on this valuation.') }}</p>
                  @endif

                  @if ($valuation['documents'] !== [])
                    <ul class="flex flex-wrap gap-2" aria-label="{{ __('Documents') }}">
                      @foreach ($valuation['documents'] as $document)
                        <li>
                          <a href="{{ $document['url'] }}" data-no-instant class="focus-ring flex items-center gap-2.5 rounded-md border border-hairline py-2 ps-2 pe-3 no-underline transition-colors hover:bg-hover">
                            <span class="flex size-7 items-center justify-center rounded-md bg-card text-muted" aria-hidden="true"><x-icon name="file" :size="14" /></span>
                            <span class="flex flex-col">
                              <span class="text-sm font-medium text-ink">{{ $document['name'] }}</span>
                              <span class="text-xs text-muted">{{ $document['meta'] }}</span>
                            </span>
                          </a>
                        </li>
                      @endforeach
                    </ul>
                  @endif
                </div>

                <p class="border-t border-hairline bg-card px-4 py-2.5 text-xs text-muted">{{ $valuation['stamp'] }}</p>
              </li>
            @endforeach
          </ol>
        </div>
      @endif
    </section>
  </div>
</x-shell>
