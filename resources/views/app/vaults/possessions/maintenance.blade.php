{{-- The Maintenance section of a possession: every job done on it, with the condition before and after. --}}
{{-- @var \App\ViewModels\Vaults\PossessionMaintenanceViewModel $viewModel --}}
@php
  $page = $viewModel->page();
  $vault = $page->vault();
  $records = $viewModel->records();
  $formOpen = $errors->any() || $viewModel->form()['isEditing'];
@endphp

<x-shell :title="__('Maintenance of :name', ['name' => $page->name()])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$page->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$page->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @include('app.vaults.possessions._header', ['page' => $page])

  <div class="grid grid-cols-1 items-start gap-x-8 gap-y-6 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
    @include('app.vaults.possessions._sections', ['page' => $page, 'current' => 'maintenance'])

    <section x-data="{ adding: @js($formOpen) }" aria-labelledby="maintenance-title" class="flex min-w-0 flex-col gap-5">
      <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-1">
          <div class="flex items-center gap-2">
            <h2 id="maintenance-title" class="text-section-title text-ink">{{ __('Maintenance') }}</h2>
            <x-help
              :label="__('Help about maintenance records')"
              :title="__('One record per job')"
              guide="customise.possession-maintenance#when-a-job-is-overdue"
            >
              {{ __('Add a record each time work is done on this possession. The condition before and after shows what the job changed. Only the next due date of the latest job counts, since a later job replaces the schedule of an earlier one.') }}
            </x-help>
          </div>
          <p class="text-sm text-pretty text-muted">{{ __('The work done on this copy over time, with the condition before and after each job.') }}</p>
        </div>

        <div x-show="! adding">
          <x-button.secondary size="small" type="button" @click="adding = true; $nextTick(() => document.getElementById('mnt-title')?.focus())">
            <x-slot:icon><x-icon name="plus" :size="16" /></x-slot:icon>
            {{ __('Maintenance record') }}
          </x-button.secondary>
        </div>
      </div>

      <div x-show="adding" x-cloak>
        @include('app.vaults.possessions._maintenance-form', ['viewModel' => $viewModel])
      </div>

      @if ($records === [])
        <p class="rounded-lg border border-dashed border-hairline-strong px-4 py-5 text-sm text-muted">{{ __('No maintenance recorded yet. Add a record when work is done on this possession.') }}</p>
      @else
        <ol class="flex flex-col gap-4">
          @foreach ($records as $record)
            <li
              x-data="{ confirming: false }"
              aria-labelledby="maintenance-{{ $record['id'] }}-title"
              class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel"
            >
              <div class="flex flex-wrap items-center gap-x-4 gap-y-3 p-4">
                <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                  <span class="inline-flex h-7 shrink-0 items-center gap-1.5 rounded-full px-2.5 text-sm font-medium {{ $record['typeTint'] }}">
                    <span class="size-1.75 rounded-full" style="background: {{ $record['typeColour'] }}" aria-hidden="true"></span>
                    {{ $record['type'] }}
                  </span>
                  <h3 id="maintenance-{{ $record['id'] }}-title" class="min-w-0 truncate text-base font-semibold text-ink" title="{{ $record['title'] }}">{{ $record['title'] }}</h3>
                  @if ($record['isOverdue'])
                    <x-badge tone="warning" icon="bell">{{ __('Overdue') }}</x-badge>
                  @endif
                </div>

                <div class="flex flex-col gap-0.5 text-end">
                  <span @class(['text-[18px] font-semibold tracking-[-0.01em] tabular-nums', 'text-ink' => $record['hasCost'], 'text-muted' => ! $record['hasCost']])>{{ $record['cost'] }}</span>
                  @if ($record['hasCost'])
                    <span class="text-xs text-muted">{{ __('cost') }}</span>
                  @endif
                </div>

                <div class="flex gap-1.5">
                  <x-button.secondary size="small" :href="$record['editUrl']">
                    {{ __('Edit') }}<span class="sr-only">, {{ $record['title'] }}</span>
                  </x-button.secondary>
                  <x-button variant="ghost" size="small" type="button" @click="confirming = true; $nextTick(() => $refs.keep.focus())" ::aria-expanded="confirming ? 'true' : 'false'" aria-controls="maintenance-{{ $record['id'] }}-delete">
                    {{ __('Delete') }}<span class="sr-only">, {{ $record['title'] }}</span>
                  </x-button>
                </div>
              </div>

              <div
                id="maintenance-{{ $record['id'] }}-delete"
                x-show="confirming"
                x-cloak
                @keydown.escape="confirming = false"
                role="alertdialog"
                aria-labelledby="maintenance-{{ $record['id'] }}-delete-title"
                aria-describedby="maintenance-{{ $record['id'] }}-delete-body"
                class="flex flex-wrap items-center gap-x-4 gap-y-2.5 border-y border-error-edge bg-error-tint px-4 py-3"
              >
                <div class="min-w-60 flex-1">
                  <p id="maintenance-{{ $record['id'] }}-delete-title" class="text-sm font-semibold text-ink">{{ $record['deleteTitle'] }}</p>
                  <p id="maintenance-{{ $record['id'] }}-delete-body" class="text-sm text-pretty text-body">{{ __('The maintenance history loses this job. This cannot be undone.') }}</p>
                </div>
                <x-button.secondary size="small" type="button" x-ref="keep" @click="confirming = false">{{ __('Keep it') }}</x-button.secondary>
                <x-form method="delete" :action="$record['destroyUrl']">
                  <x-button.danger size="small">{{ __('Delete') }}</x-button.danger>
                </x-form>
              </div>

              <dl class="grid grid-cols-[repeat(auto-fit,minmax(9.375rem,1fr))] border-t border-hairline">
                @foreach ($record['parts'] as $part)
                  <div class="flex min-w-0 flex-col gap-0.75 border-e border-hairline-soft px-4 py-3">
                    <dt class="text-xs text-muted">{{ $part['label'] }}</dt>
                    <dd @class(['truncate text-base', 'text-ink' => $part['isSet'], 'text-muted' => ! $part['isSet']]) title="{{ $part['value'] }}">{{ $part['value'] }}</dd>
                  </div>
                @endforeach
              </dl>

              @if ($record['description'] !== null)
                <div class="border-t border-hairline px-4 py-3.5">
                  <p class="max-w-reading text-sm text-pretty whitespace-pre-line text-body">{{ $record['description'] }}</p>
                </div>
              @endif
            </li>
          @endforeach
        </ol>
      @endif
    </section>
  </div>
</x-shell>
