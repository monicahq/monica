{{-- The Loans section of a possession: who has it now and every time it left or came into your hands. --}}
{{-- @var \App\ViewModels\Vaults\PossessionLoansViewModel $viewModel --}}
@php
  $page = $viewModel->page();
  $vault = $page->vault();
  $active = $viewModel->active();
  $rows = $viewModel->rows();
  $formOpen = $errors->any() || $viewModel->form()['isEditing'];
@endphp

<x-shell :title="__('Loans of :name', ['name' => $page->name()])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$page->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$page->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @include('app.vaults.possessions._header', ['page' => $page])

  <div class="grid grid-cols-1 items-start gap-x-8 gap-y-6 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
    @include('app.vaults.possessions._sections', ['page' => $page, 'current' => 'loans'])

    <section
      x-data="{ lending: @js($formOpen) }"
      aria-labelledby="loans-title"
      class="flex min-w-0 flex-col gap-5"
    >
      <div class="flex flex-wrap items-start gap-x-4 gap-y-3">
        <div class="flex min-w-65 flex-1 flex-col gap-1">
          <div class="flex items-center gap-2">
            <h2 id="loans-title" class="text-section-title text-ink">{{ __('Loans') }}</h2>
            <x-help
              :label="__('Help about loans')"
              :title="__('How loans work')"
              guide="customise.possession-loans"
            >
              {{ __('A loan records who has the possession and when it is due back. Ownership does not change, so nothing is added to Transactions. The person comes from your contacts, so the loan also appears on their page.') }}
            </x-help>
          </div>
          <p class="text-sm text-pretty text-muted">{{ __('Custody moving out and in over time, without any change of ownership. An active loan means the possession is not in your hands right now.') }}</p>
        </div>

        <x-button size="small" type="button" id="loan-add-button" x-show="! lending" @click="lending = true; $nextTick(() => document.getElementById('loan-direction')?.focus())">
          <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
          {{ __('Add a loan') }}
        </x-button>
      </div>

      @if ($active !== null)
        <x-panel flush>
          <div class="flex flex-wrap items-center gap-x-3.5 gap-y-3 p-4">
            <x-avatar-initials :name="$active['name']" :size="36" />
            <div class="flex min-w-50 flex-1 flex-col gap-0.75">
              <span class="text-base text-ink">{{ $active['sentence'] }}</span>
              <span @class(['text-sm', 'text-error' => $active['isOverdue'], 'text-muted' => ! $active['isOverdue']])>{{ $active['due'] }}</span>
            </div>
            <x-button.secondary size="small" :href="$active['returnUrl']">{{ __('Mark as returned') }}</x-button.secondary>
          </div>
        </x-panel>
      @else
        <p class="flex items-center gap-2 text-sm text-muted">
          <span class="flex" aria-hidden="true"><x-icon name="check" :size="14" /></span>
          {{ __('Not lent to anyone right now.') }}
        </p>
      @endif

      <div x-show="lending" x-cloak>
        @include('app.vaults.possessions._loan-form', ['viewModel' => $viewModel])
      </div>

      @if ($rows !== [])
        <div class="flex flex-col gap-3">
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h3 class="text-card-title text-ink">{{ __('Every loan') }}</h3>
            <span class="text-sm text-muted">{{ $viewModel->countLine() }}</span>
            <span class="ms-auto text-xs text-muted">{{ __('Newest first') }}</span>
          </div>

          <ol class="flex flex-col gap-3">
            @foreach ($rows as $row)
              <li
                x-data="{ confirming: false }"
                aria-labelledby="loan-{{ $row['id'] }}-title"
                class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel"
              >
                <div class="flex flex-wrap items-start gap-x-4 gap-y-3 p-4">
                  <x-avatar-initials :name="$row['name']" :size="32" />

                  <div class="flex min-w-55 flex-1 flex-col gap-1">
                    <div class="flex flex-wrap items-baseline gap-1.5">
                      <h4 id="loan-{{ $row['id'] }}-title" class="text-base text-ink">
                        <span class="text-muted">{{ $row['verb'] }}</span>
                        @if ($row['contactUrl'] !== null)
                          <x-link :href="$row['contactUrl']" class="font-semibold">{{ $row['name'] }}</x-link>
                        @else
                          <span class="font-semibold">{{ $row['name'] }}</span>
                        @endif
                      </h4>
                      <x-badge :tone="$row['tone']">{{ $row['status'] }}</x-badge>
                      @if ($row['isInProvenance'])
                        <x-badge>{{ __('In the provenance') }}</x-badge>
                      @endif
                    </div>
                    <span class="text-sm text-body">{{ $row['period'] }} <span class="text-muted">· {{ $row['span'] }}</span></span>
                    @if ($row['purpose'] !== null)
                      <p class="max-w-reading text-sm text-pretty whitespace-pre-line text-body">{{ $row['purpose'] }}</p>
                    @endif
                  </div>

                  <div class="flex flex-wrap gap-1.5">
                    @if ($row['canReturn'])
                      <x-button.secondary size="small" :href="$row['returnUrl']">
                        {{ __('Mark as returned') }}<span class="sr-only">, {{ $row['name'] }}</span>
                      </x-button.secondary>
                    @endif
                    <x-button.secondary size="small" :href="$row['editUrl']">
                      {{ __('Edit') }}<span class="sr-only">, {{ $row['name'] }}</span>
                    </x-button.secondary>
                    <x-button variant="ghost" size="small" type="button" x-ref="deleteTrigger" @click="confirming = true; $nextTick(() => $refs.keep.focus())" ::aria-expanded="confirming ? 'true' : 'false'" aria-controls="loan-{{ $row['id'] }}-delete">
                      {{ __('Delete') }}<span class="sr-only">, {{ $row['name'] }}</span>
                    </x-button>
                  </div>
                </div>

                <dl class="grid grid-cols-[repeat(auto-fit,minmax(10rem,1fr))] border-t border-hairline">
                  @foreach ($row['facts'] as $fact)
                    <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3 last:border-e-0">
                      <dt class="text-xs text-muted">{{ $fact['label'] }}</dt>
                      <dd @class(['text-sm', 'text-ink' => $fact['isSet'], 'text-muted' => ! $fact['isSet']])>{{ $fact['value'] }}</dd>
                    </div>
                  @endforeach
                </dl>

                <div
                  id="loan-{{ $row['id'] }}-delete"
                  x-show="confirming"
                  x-cloak
                  @keydown.escape="confirming = false; $nextTick(() => $refs.deleteTrigger.focus())"
                  role="alertdialog"
                  aria-labelledby="loan-{{ $row['id'] }}-delete-title"
                  aria-describedby="loan-{{ $row['id'] }}-delete-body"
                  class="flex flex-wrap items-center gap-x-4 gap-y-2.5 border-t border-error-edge bg-error-tint px-4 py-3"
                >
                  <div class="min-w-60 flex-1">
                    <p id="loan-{{ $row['id'] }}-delete-title" class="text-sm font-semibold text-ink">{{ $row['deleteTitle'] }}</p>
                    <p id="loan-{{ $row['id'] }}-delete-body" class="text-sm text-pretty text-body">{{ $row['deleteWarning'] }}</p>
                  </div>
                  <x-button.secondary size="small" type="button" x-ref="keep" @click="confirming = false; $nextTick(() => $refs.deleteTrigger.focus())">{{ __('Keep it') }}</x-button.secondary>
                  <x-form method="delete" :action="$row['destroyUrl']">
                    <x-button.danger size="small">{{ __('Delete') }}</x-button.danger>
                  </x-form>
                </div>
              </li>
            @endforeach
          </ol>
        </div>
      @else
        <x-empty-state :title="__('No loan recorded')">
          <x-slot:icon><x-icon name="relationship" :size="20" /></x-slot:icon>
          {{ __('Each time the possession leaves your hands without changing owner, record it here so you know who has it and when it is due back.') }}
        </x-empty-state>
      @endif
    </section>
  </div>
</x-shell>
