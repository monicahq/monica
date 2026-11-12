{{-- The Insurance section of a possession: what it is insured for, against its value, and every record. --}}
{{-- @var \App\ViewModels\Vaults\PossessionInsuranceViewModel $viewModel --}}
@php
  $page = $viewModel->page();
  $vault = $page->vault();
  $summary = $viewModel->summary();
  $warning = $viewModel->underInsuranceWarning();
  $records = $viewModel->records();
  $formOpen = $errors->any() || $viewModel->form()['isEditing'];
@endphp

<x-shell :title="__('Insurance of :name', ['name' => $page->name()])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$page->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$page->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @include('app.vaults.possessions._header', ['page' => $page])

  <div class="grid grid-cols-1 items-start gap-x-8 gap-y-6 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
    @include('app.vaults.possessions._sections', ['page' => $page, 'current' => 'insurance'])

    <section x-data="{ adding: @js($formOpen) }" aria-labelledby="insurance-title" class="flex min-w-0 flex-col gap-5">
      <div class="flex flex-wrap items-start gap-x-4 gap-y-3">
        <div class="flex min-w-65 flex-1 flex-col gap-1">
          <div class="flex items-center gap-2">
            <h2 id="insurance-title" class="text-section-title text-ink">{{ __('Insurance') }}</h2>
            <x-help
              :label="__('Help about insurance records')"
              :title="__('One record per policy period')"
              guide="customise.possession-insurance"
            >
              {{ __('When a policy renews with a new insured value, or you change insurer, add a new record rather than editing the old one. The earlier records stay as the history of how this possession was covered.') }}
            </x-help>
          </div>
          <p class="text-sm text-pretty text-muted">{{ __('Coverage records as policies and insured values change over time.') }}</p>
        </div>

        <x-button size="small" type="button" x-show="! adding" @click="adding = true; $nextTick(() => document.getElementById('ins-insurer')?.focus())">
          <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
          {{ __('Add an insurance record') }}
        </x-button>
      </div>

      <x-panel flush>
        <dl class="grid grid-cols-[repeat(auto-fit,minmax(11.875rem,1fr))]">
          <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Insured for now') }}</dt>
            <dd class="text-base font-semibold text-ink tabular-nums">{{ $summary['insured'] }}</dd>
            <dd class="text-xs text-muted">{{ $summary['insuredNote'] }}</dd>
          </div>
          <div class="flex flex-col gap-0.75 border-e border-hairline px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Current estimated value') }}</dt>
            <dd class="text-base font-semibold text-ink tabular-nums">{{ $summary['worth'] }}</dd>
            <dd @class(['text-xs', 'text-warning' => $summary['isUnderInsured'], 'text-muted' => ! $summary['isUnderInsured']])>{{ $summary['worthNote'] }}</dd>
          </div>
          <div class="flex flex-col gap-0.75 px-4 py-3.5">
            <dt class="text-xs text-muted">{{ __('Next renewal') }}</dt>
            <dd class="text-base font-semibold text-ink">{{ $summary['renewal'] }}</dd>
            @if ($summary['renewalNote'] !== '')
              <dd class="text-xs text-muted">{{ $summary['renewalNote'] }}</dd>
            @endif
          </div>
        </dl>
      </x-panel>

      @if ($warning !== null)
        <div x-show="! adding">
          <x-notice tone="warning" icon="info" :title="__('Covered for less than it is worth')">{{ $warning }}</x-notice>
        </div>
      @endif

      <div x-show="adding" x-cloak>
        @include('app.vaults.possessions._insurance-form', ['viewModel' => $viewModel])
      </div>

      @if ($records === [])
        <p class="rounded-lg border border-dashed border-hairline-strong px-4 py-5 text-sm text-muted">{{ __('No insurance recorded yet.') }}</p>
      @else
        <ol class="flex flex-col gap-4">
          @foreach ($records as $record)
            <li
              x-data="{ confirming: false }"
              aria-labelledby="insurance-{{ $record['id'] }}-title"
              class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel"
            >
              <div class="flex flex-wrap items-start gap-x-4 gap-y-3 p-4">
                <div class="flex min-w-55 flex-1 flex-col gap-1.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 id="insurance-{{ $record['id'] }}-title" class="text-base font-semibold text-ink">{{ $record['insurer'] }}</h3>
                    <x-badge :tone="$record['tone']" :icon="$record['icon']">{{ $record['status'] }}</x-badge>
                  </div>
                  <span @class(['text-sm', 'text-body' => $record['coverage'] !== null, 'text-muted' => $record['coverage'] === null])>
                    {{ $record['coverage'] ?? __('No coverage described') }}
                  </span>
                </div>

                <div class="flex flex-col gap-0.5 text-end">
                  <span class="text-[18px] font-semibold tracking-[-0.01em] text-ink tabular-nums">{{ $record['value'] }}</span>
                  <span class="text-xs text-muted">{{ __('insured value') }}</span>
                </div>

                <div class="flex gap-1.5">
                  <x-button.secondary size="small" :href="$record['editUrl']">
                    {{ __('Edit') }}<span class="sr-only">, {{ $record['insurer'] }}</span>
                  </x-button.secondary>
                  <x-button variant="ghost" size="small" type="button" @click="confirming = true; $nextTick(() => $refs.keep.focus())" ::aria-expanded="confirming ? 'true' : 'false'" aria-controls="insurance-{{ $record['id'] }}-delete">
                    {{ __('Delete') }}<span class="sr-only">, {{ $record['insurer'] }}</span>
                  </x-button>
                </div>
              </div>

              <div
                id="insurance-{{ $record['id'] }}-delete"
                x-show="confirming"
                x-cloak
                @keydown.escape="confirming = false"
                role="alertdialog"
                aria-labelledby="insurance-{{ $record['id'] }}-delete-title"
                aria-describedby="insurance-{{ $record['id'] }}-delete-body"
                class="flex flex-wrap items-center gap-x-4 gap-y-2.5 border-y border-error-edge bg-error-tint px-4 py-3"
              >
                <div class="min-w-60 flex-1">
                  <p id="insurance-{{ $record['id'] }}-delete-title" class="text-sm font-semibold text-ink">{{ $record['deleteTitle'] }}</p>
                  <p id="insurance-{{ $record['id'] }}-delete-body" class="text-sm text-pretty text-body">{{ __('The coverage history loses this policy period. This cannot be undone.') }}</p>
                </div>
                <x-button.secondary size="small" type="button" x-ref="keep" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>
                <x-form method="delete" :action="$record['destroyUrl']">
                  <x-button.danger size="small">{{ __('Delete') }}</x-button.danger>
                </x-form>
              </div>

              <dl class="grid grid-cols-[repeat(auto-fit,minmax(9.375rem,1fr))] border-t border-hairline">
                @foreach ($record['parts'] as $part)
                  <div class="flex flex-col gap-0.75 border-e border-hairline-soft px-4 py-3">
                    <dt class="text-xs text-muted">{{ $part['label'] }}</dt>
                    <dd @class(['text-base tabular-nums', 'text-ink' => $part['isSet'], 'text-muted' => ! $part['isSet'], 'font-mono' => $part['isSet'] && $part['isCode']])>{{ $part['value'] }}</dd>
                    @if ($part['sub'] !== null)
                      <dd class="text-xs text-muted">{{ $part['sub'] }}</dd>
                    @endif
                  </div>
                @endforeach
              </dl>

              @if ($record['contactName'] !== null || $record['email'] !== null || $record['phone'] !== null)
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-hairline px-4 py-3 text-sm">
                  <span class="flex items-center gap-2">
                    <x-avatar-initials :name="$record['avatarName']" :size="24" />
                    <span @class(['font-medium', 'text-ink' => $record['contactName'] !== null, 'text-muted' => $record['contactName'] === null])>{{ $record['contactName'] ?? __('No name recorded') }}</span>
                  </span>
                  @if ($record['email'] !== null)
                    <span class="flex min-w-0 items-center gap-1.5">
                      <span class="flex text-muted" aria-hidden="true"><x-icon name="mail" :size="14" /></span>
                      <x-link :href="'mailto:'.$record['email']" :turbo="false" class="truncate">{{ $record['email'] }}</x-link>
                    </span>
                  @endif
                  @if ($record['phone'] !== null)
                    <span class="flex items-center gap-1.5">
                      <span class="text-muted">{{ __('Phone') }}</span>
                      <x-link :href="'tel:'.$record['tel']" :turbo="false">{{ $record['phone'] }}</x-link>
                    </span>
                  @endif
                </div>
              @endif

              <div class="border-t border-hairline px-4 py-3.5">
                @if ($record['note'] !== null)
                  <p class="max-w-reading text-sm text-pretty whitespace-pre-line text-body">{{ $record['note'] }}</p>
                @else
                  <p class="text-sm text-muted">{{ __('No note on this record.') }}</p>
                @endif
              </div>

              <p class="border-t border-hairline bg-card px-4 py-2.5 text-xs text-muted">{{ $record['stamp'] }}</p>
            </li>
          @endforeach
        </ol>
      @endif
    </section>
  </div>
</x-shell>
