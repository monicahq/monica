{{-- The Transactions section of a possession: how it came in, what it cost since, and every transaction. --}}
{{-- @var \App\ViewModels\Vaults\PossessionTransactionsViewModel $viewModel --}}
@php
  $page = $viewModel->page();
  $vault = $page->vault();
  $summary = $viewModel->summary();
  $counts = $viewModel->counts();
  $transactions = $viewModel->transactions();
  $formOpen = $errors->any() || $viewModel->form()['isEditing'];
@endphp

<x-shell :title="__('Transactions of :name', ['name' => $page->name()])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$page->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$page->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @include('app.vaults.possessions._header', ['page' => $page])

  <div class="grid grid-cols-1 items-start gap-x-8 gap-y-6 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
    @include('app.vaults.possessions._sections', ['page' => $page, 'current' => 'transactions'])

    <section
      x-data="{ adding: @js($formOpen), view: @js(session('transactionsView') ?? 'ownership') }"
      aria-labelledby="transactions-title"
      class="flex min-w-0 flex-col gap-5"
    >
      <div class="flex flex-wrap items-start gap-x-4 gap-y-3">
        <div class="flex min-w-65 flex-1 flex-col gap-1">
          <div class="flex items-center gap-2">
            <h2 id="transactions-title" class="text-section-title text-ink">{{ __('Transactions') }}</h2>
            <x-help
              :label="__('Help about the acquisition')"
              :title="__('Where the acquisition comes from')"
              guide="customise.possession-transactions#why-it-matters"
            >
              {{ __('The first purchase, exchange, gift received or inheritance gives this possession its acquisition date and price. Nothing is copied onto the possession itself, so editing that transaction changes both.') }}
            </x-help>
          </div>
          <p class="text-sm text-pretty text-muted">{{ __('Money and ownership changing hands. Prices, fees and totals are read from here.') }}</p>
        </div>

        <x-button size="small" type="button" x-show="! adding" @click="adding = true; $nextTick(() => document.getElementById('tx-type')?.focus())">
          <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
          {{ __('Add a transaction') }}
        </x-button>
      </div>

      <x-panel flush>
        <dl class="grid grid-cols-[repeat(auto-fit,minmax(11.875rem,1fr))]">
          <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Acquired') }}</dt>
            <dd class="text-base font-semibold text-ink">{{ $summary['acquired'] }}</dd>
            <dd class="text-xs text-muted">{{ $summary['how'] }}</dd>
          </div>
          <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Acquisition price') }}</dt>
            <dd class="text-base font-semibold text-ink tabular-nums">{{ $summary['price'] }}</dd>
            <dd class="text-xs text-muted">{{ $summary['priceNote'] }}</dd>
          </div>
          <div class="flex flex-col gap-0.75 px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Costs since') }}</dt>
            <dd class="text-base font-semibold text-ink tabular-nums">{{ $summary['costs'] }}</dd>
            @if ($summary['costsNote'] !== '')
              <dd class="text-xs text-muted">{{ $summary['costsNote'] }}</dd>
            @endif
          </div>
        </dl>
      </x-panel>

      <div x-show="adding" x-cloak>
        @include('app.vaults.possessions._transaction-form', ['viewModel' => $viewModel])
      </div>

      @if ($transactions === [])
        <p class="rounded-lg border border-dashed border-hairline-strong px-4 py-5 text-sm text-muted">{{ __('No transaction recorded yet.') }}</p>
      @else
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('Which transactions to show') }}">
          <x-chip :count="$counts['ownership']" :selected="session('transactionsView') !== 'all'" @click="view = 'ownership'" x-effect="$el.setAttribute('aria-pressed', view === 'ownership' ? 'true' : 'false')">
            {{ __('Ownership changes') }}
          </x-chip>
          <x-chip :count="$counts['all']" :selected="session('transactionsView') === 'all'" @click="view = 'all'" x-effect="$el.setAttribute('aria-pressed', view === 'all' ? 'true' : 'false')">
            {{ __('All transactions') }}
          </x-chip>
          <span class="ms-auto text-xs text-muted">{{ __('Newest first') }}</span>
        </div>

        <ol class="flex flex-col gap-4">
          @foreach ($transactions as $transaction)
            <li
              x-data="{ confirming: false }"
              @if (! $transaction['changesOwnership']) x-show="view === 'all'" @endif
              aria-labelledby="transaction-{{ $transaction['id'] }}-title"
              class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel"
            >
              <div class="flex flex-wrap items-start gap-x-4 gap-y-3 p-4">
                <div class="flex min-w-55 flex-1 flex-col gap-1.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <x-badge :icon="$transaction['icon']">{{ $transaction['type'] }}</x-badge>
                    @if ($transaction['isAcquisition'])
                      <x-badge tone="success" icon="check">{{ __('Acquisition') }}</x-badge>
                    @endif
                    <span class="text-sm text-muted">{{ $transaction['date'] }}</span>
                  </div>
                  <h3 id="transaction-{{ $transaction['id'] }}-title" @class(['text-base', 'text-ink' => $transaction['counterparty'] !== null, 'text-muted' => $transaction['counterparty'] === null])>
                    {{ $transaction['counterparty'] ?? __('No counterparty recorded') }}
                  </h3>
                </div>

                <div class="flex flex-col gap-0.5 text-end">
                  <span class="text-[18px] font-semibold tracking-[-0.01em] text-ink tabular-nums">{{ $transaction['total'] }}</span>
                  <span class="text-xs text-muted">{{ $transaction['totalHow'] }}</span>
                </div>

                <div class="flex gap-1.5">
                  <x-button.secondary size="small" :href="$transaction['editUrl']">
                    {{ __('Edit') }}<span class="sr-only">, {{ $transaction['type'] }}, {{ $transaction['date'] }}</span>
                  </x-button.secondary>
                  <x-button variant="ghost" size="small" type="button" @click="confirming = true; $nextTick(() => $refs.keep.focus())" ::aria-expanded="confirming ? 'true' : 'false'" aria-controls="transaction-{{ $transaction['id'] }}-delete">
                    {{ __('Delete') }}<span class="sr-only">, {{ $transaction['type'] }}, {{ $transaction['date'] }}</span>
                  </x-button>
                </div>
              </div>

              <div
                id="transaction-{{ $transaction['id'] }}-delete"
                x-show="confirming"
                x-cloak
                @keydown.escape="confirming = false"
                role="alertdialog"
                aria-labelledby="transaction-{{ $transaction['id'] }}-delete-title"
                aria-describedby="transaction-{{ $transaction['id'] }}-delete-body"
                class="flex flex-wrap items-center gap-x-4 gap-y-2.5 border-y border-error-edge bg-error-tint px-4 py-3"
              >
                <div class="min-w-60 flex-1">
                  <p id="transaction-{{ $transaction['id'] }}-delete-title" class="text-sm font-semibold text-ink">{{ __('Delete this transaction?') }}</p>
                  <p id="transaction-{{ $transaction['id'] }}-delete-body" class="text-sm text-pretty text-body">{{ $transaction['deleteWarning'] }}</p>
                </div>
                <x-button.secondary size="small" type="button" x-ref="keep" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>
                <x-form method="delete" :action="$transaction['destroyUrl']">
                  <x-button.danger size="small">{{ __('Delete') }}</x-button.danger>
                </x-form>
              </div>

              <dl class="grid grid-cols-[repeat(auto-fit,minmax(8.125rem,1fr))] border-t border-hairline">
                @foreach ($transaction['parts'] as $part)
                  <div class="flex flex-col gap-0.75 border-e border-hairline-soft px-4 py-3">
                    <dt class="text-xs text-muted">{{ $part['label'] }}</dt>
                    <dd @class(['text-base tabular-nums', 'text-ink' => $part['isSet'], 'text-muted' => ! $part['isSet']])>{{ $part['value'] }}</dd>
                  </div>
                @endforeach
              </dl>

              <div class="flex flex-col gap-2.5 border-t border-hairline px-4 py-3.5">
                @if ($transaction['note'] !== null)
                  <p class="max-w-reading text-sm text-pretty whitespace-pre-line text-body">{{ $transaction['note'] }}</p>
                @else
                  <p class="text-sm text-muted">{{ __('No note on this transaction.') }}</p>
                @endif

                @if ($transaction['reference'] !== null || $transaction['sourceUrl'] !== null)
                  <div class="flex flex-wrap gap-x-5 gap-y-1.5 text-sm">
                    @if ($transaction['reference'] !== null)
                      <span class="flex items-center gap-1.5">
                        <span class="text-muted">{{ __('Reference') }}</span>
                        <span class="font-mono text-ink-soft">{{ $transaction['reference'] }}</span>
                      </span>
                    @endif
                    @if ($transaction['sourceUrl'] !== null)
                      <span class="flex min-w-0 items-center gap-1.5">
                        <span class="flex text-muted" aria-hidden="true"><x-icon name="link" :size="14" /></span>
                        <x-link :href="$transaction['sourceUrl']" :turbo="false" rel="noopener noreferrer" target="_blank" class="truncate">{{ $transaction['sourceUrl'] }}</x-link>
                      </span>
                    @endif
                  </div>
                @endif
              </div>

              <div class="flex flex-col gap-2.5 border-t border-hairline px-4 pt-3 pb-4">
                <x-caption>{{ __('Documents') }}</x-caption>
                @if ($transaction['documents'] === [])
                  <p class="rounded-md border border-dashed border-hairline-strong p-3 text-sm text-muted">{{ __('No invoice or receipt attached yet.') }}</p>
                @else
                  <ul class="flex flex-wrap gap-2">
                    @foreach ($transaction['documents'] as $document)
                      <li class="flex items-center gap-1">
                        <a href="{{ $document['url'] }}" data-no-instant class="focus-ring flex items-center gap-2.5 rounded-md border border-hairline py-2 ps-2 pe-3 no-underline transition-colors hover:bg-hover">
                          <span class="flex size-7 items-center justify-center rounded-md bg-card text-muted" aria-hidden="true"><x-icon name="file" :size="14" /></span>
                          <span class="flex flex-col">
                            <span class="text-sm font-medium text-ink">{{ $document['name'] }}</span>
                            <span class="text-xs text-muted">{{ $document['meta'] }}</span>
                          </span>
                        </a>
                        <x-delete-action
                          :title="__('Remove :name from this transaction?', ['name' => $document['name']])"
                          :confirm="__('Remove the document')"
                          :action="$document['destroyUrl']"
                          :label="__('Remove')"
                          variant="ghost"
                          size="small"
                        >
                          <p>{{ __(':name is no longer attached to this transaction. It stays wherever else it is used.', ['name' => $document['name']]) }}</p>
                        </x-delete-action>
                      </li>
                    @endforeach
                  </ul>
                @endif
                @if ($viewModel->canAttachDocuments())
                  <x-form method="post" :action="$transaction['attachUrl']" upload class="flex flex-wrap items-end gap-3">
                    <x-file-input :id="'tx-document-'.$transaction['id']" name="document" :label="__('Attach an invoice or a receipt')" :error="$errors->get('document.'.$transaction['id'])" required />
                    <x-button size="small" variant="secondary">{{ __('Attach') }}</x-button>
                  </x-form>
                @endif
              </div>

              <p class="border-t border-hairline bg-card px-4 py-2.5 text-xs text-muted">{{ $transaction['stamp'] }}</p>
            </li>
          @endforeach
        </ol>
      @endif
    </section>
  </div>
</x-shell>
