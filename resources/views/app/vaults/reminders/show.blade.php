{{-- One reminder, explained. --}}
{{-- @var \App\ViewModels\Vaults\ReminderViewModel $viewModel --}}
@php
  $state = $viewModel->state();
  $next = $viewModel->next();
  $schedule = $viewModel->schedule();
  $contact = $viewModel->contact();
  $occurrences = $viewModel->occurrences();
  $nextRow = $occurrences[0] ?? null;
  $history = $viewModel->history();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('Reminders'), 'url' => $viewModel->remindersUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="reminders" />
  </x-slot:sidebar>

  @if ($contact !== null)
    @include('app.vaults.reminders._contact-card', [
      'contact' => $contact,
      'builtFrom' => $viewModel->builtFrom(),
    ])
  @endif

  <div class="flex flex-wrap items-start justify-between gap-4">
    <div class="flex min-w-0 flex-col gap-2">
      <x-page-header :title="$viewModel->title()" />

      <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
        <x-badge :tone="$state['tone']" :icon="$state['icon']">{{ $state['label'] }}</x-badge>

        <span class="text-sm text-body">{{ $viewModel->builtFrom() }}</span>

        <span aria-hidden="true" class="text-sm text-muted-soft">·</span>

        <span class="text-sm text-muted">{{ $viewModel->createdLine() }}</span>
      </div>
    </div>

    <div class="flex shrink-0 items-center gap-2">
      @if ($state['isActive'])
        <x-form method="put" :action="$viewModel->pauseUrl()" class="contents">
          <x-button.secondary type="submit">{{ __('Pause') }}</x-button.secondary>
        </x-form>
      @elseif ($state['canResume'])
        <x-form method="delete" :action="$viewModel->resumeUrl()" class="contents">
          <x-button.secondary type="submit">{{ __('Start it again') }}</x-button.secondary>
        </x-form>
      @endif

      <x-button :href="$viewModel->editUrl()" data-turbo="true">{{ __('Edit reminder') }}</x-button>
    </div>
  </div>

  <div
    @class([
      'flex flex-wrap items-center gap-x-6 gap-y-3 rounded-lg border px-5 py-4',
      'border-success-edge bg-success-tint' => $next['tone'] === 'success',
      'border-hairline bg-card' => $next['tone'] !== 'success',
    ])
  >
    <div class="flex min-w-0 flex-1 flex-col gap-1">
      <span
        @class([
          'text-[11px] font-semibold tracking-caption uppercase',
          'text-success' => $next['tone'] === 'success',
          'text-muted' => $next['tone'] !== 'success',
        ])
      >{{ $next['overline'] }}</span>

      <span class="text-xl font-semibold tracking-tight text-ink">{{ $next['date'] }}</span>

      <span class="text-sm text-pretty text-body">{{ $next['note'] }}</span>
    </div>

    @if ($next['isScheduled'] && $nextRow !== null)
      <div class="flex flex-none items-center gap-1.5">
        @include('app.vaults.reminders._postpone', ['row' => $nextRow, 'inMenu' => false])

        @include('app.vaults.reminders._dismiss', ['row' => $nextRow])
      </div>
    @endif
  </div>

  <x-panel :title="__('Schedule')" :subtitle="__('How this reminder decides when to tell you.')" icon="calendar-repeat">
    <div class="flex flex-col gap-4.5">
      <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($schedule['fields'] as $field)
          <div class="flex min-w-0 flex-col gap-1">
            <dt class="text-[11px] font-semibold tracking-caption uppercase text-muted">{{ $field['label'] }}</dt>
            <dd class="text-sm text-ink">{{ $field['value'] }}</dd>

            @if ($field['note'] !== null)
              <dd class="text-xs text-pretty text-muted">{{ $field['note'] }}</dd>
            @endif
          </div>
        @endforeach
      </dl>

      <div class="flex flex-col gap-2.5 border-t border-hairline pt-4">
        <span class="text-sm font-medium text-ink">{{ __('You are told at these moments') }}</span>

        <ul class="flex flex-wrap items-center gap-2">
          @foreach ($schedule['moments'] as $moment)
            <li class="inline-flex h-7.5 items-center gap-2 rounded-md border border-hairline bg-card px-2.75 text-sm text-body">
              <x-icon name="calendar" :size="14" :stroke-width="1.6" class="shrink-0 text-muted" />

              <span>{{ $moment['label'] }}</span>

              @if ($moment['date'] !== null)
                <span class="text-muted-soft">{{ $moment['date'] }}</span>
              @endif
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </x-panel>

  <x-panel :title="__('Still to come')" icon="reminder" :flush="true">
    @forelse ($occurrences as $row)
      <div class="border-t border-hairline px-4 py-3.5 first:border-t-0">
        @include('app.vaults.reminders.row', ['row' => $row, 'linkToReminder' => false])
      </div>
    @empty
      <div class="p-4">
        <x-empty-state :title="__('Nothing to come')">
          <x-slot:icon>
            <x-icon name="reminder" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ $viewModel->stillToComeEmptyLine() }}
        </x-empty-state>
      </div>
    @endforelse
  </x-panel>

  @if ($history !== [])
    <x-panel :title="__('History')" icon="timeline">
      @include('app.vaults.reminders._history', ['entries' => $history])
    </x-panel>
  @endif

  <x-panel :title="__('Delete this reminder')" tone="danger" icon="info">
    <div class="flex flex-wrap items-center justify-between gap-5">
      <p class="max-w-[60ch] text-sm leading-relaxed text-pretty text-body">
        {{ __('This removes the reminder and everything recorded about it. The date it reads stays on the record, and you can ask to be reminded of it again whenever you want.') }}
      </p>

      <x-modal.warning
        :title="__('Delete “:name”?', ['name' => $viewModel->title()])"
        :confirm="__('Delete the reminder')"
        :action="$viewModel->deleteUrl()"
      >
        <x-slot:trigger>
          <x-button.danger type="button" @click="open = true">{{ __('Delete reminder') }}</x-button.danger>
        </x-slot:trigger>

        {{ __('Its history is deleted with it, and nothing further will be delivered. The date on the record is not touched.') }}
      </x-modal.warning>
    </div>
  </x-panel>
</x-shell>
