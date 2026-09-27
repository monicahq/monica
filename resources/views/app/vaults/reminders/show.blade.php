{{-- One reminder, explained. --}}
{{-- @var \App\ViewModels\Vaults\ReminderViewModel $viewModel --}}
@php
  $state = $viewModel->state();
  $next = $viewModel->next();
  $schedule = $viewModel->schedule();
  $contact = $viewModel->contact();
  $occurrences = $viewModel->occurrences();
  $answers = $viewModel->answers();
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
          'text-2xs font-semibold tracking-caption uppercase',
          'text-success' => $next['tone'] === 'success',
          'text-muted' => $next['tone'] !== 'success',
        ])
      >{{ $next['overline'] }}</span>

      <span class="text-xl font-semibold tracking-tight text-ink">{{ $next['date'] }}</span>

      <span class="text-sm text-pretty text-body">{{ $next['note'] }}</span>
    </div>

    @if ($next['row'] !== null)
      <div class="flex flex-none items-center gap-1.5">
        @include('app.vaults.reminders._answers', ['row' => $next['row']])
      </div>
    @endif
  </div>

  <x-panel :title="__('Schedule')" :subtitle="__('How this reminder decides when to tell you.')" icon="calendar-repeat">
    <div class="flex flex-col gap-4.5">
      <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($schedule['fields'] as $field)
          <div class="flex min-w-0 flex-col gap-1">
            <dt class="text-2xs font-semibold tracking-caption uppercase text-muted">{{ $field['label'] }}</dt>
            <dd class="text-sm text-ink">{{ $field['value'] }}</dd>

            @if ($field['note'] !== null)
              <dd class="text-xs text-pretty text-muted">{{ $field['note'] }}</dd>
            @endif
          </div>
        @endforeach
      </dl>
    </div>
  </x-panel>

  <x-panel :title="__('Still open')" icon="reminder" :flush="true">
    @forelse ($occurrences as $row)
      <div class="border-t border-hairline px-4 py-3.5 first:border-t-0">
        @include('app.vaults.reminders.row', ['row' => $row, 'linkToReminder' => false])
      </div>
    @empty
      <div class="p-4">
        <x-empty-state :title="__('Nothing open')">
          <x-slot:icon>
            <x-icon name="reminder" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ $viewModel->stillToComeEmptyLine() }}
        </x-empty-state>
      </div>
    @endforelse
  </x-panel>

  <x-panel :title="__('History')" :subtitle="__('What you answered for each date. Answers are kept as they are.')" icon="check" :flush="true">
    @forelse ($answers as $answer)
      <div class="flex flex-wrap items-center justify-between gap-3 border-t border-hairline px-4 py-3 first:border-t-0">
        <span class="text-sm text-ink">{{ $answer['date'] }}</span>

        <span class="flex items-center gap-2.5">
          @if ($answer['answeredOn'] !== null)
            <span class="text-xs text-muted">{{ __('answered :date', ['date' => $answer['answeredOn']]) }}</span>
          @endif

          <x-badge :tone="$answer['tone']">{{ $answer['outcome'] }}</x-badge>
        </span>
      </div>
    @empty
      <div class="p-4">
        <x-empty-state :title="__('No dates yet')">
          <x-slot:icon>
            <x-icon name="check" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('Once a date has come, it is listed here with what you answered.') }}
        </x-empty-state>
      </div>
    @endforelse
  </x-panel>

  @if ($history !== [])
    <x-panel :title="__('Activity')" icon="timeline">
      @include('app.vaults.reminders._history', ['entries' => $history])
    </x-panel>
  @endif

  <x-panel :title="__('Delete this reminder')" tone="danger" icon="info">
    <div class="flex flex-wrap items-center justify-between gap-5">
      <p class="max-w-prose text-sm leading-relaxed text-pretty text-body">
        {{ __('This stops the reminder for good. Nothing further is delivered, and dates still waiting for an answer are dropped.') }}
      </p>

      <x-modal.warning
        :title="__('Delete “:name”?', ['name' => $viewModel->title()])"
        :confirm="__('Delete the reminder')"
        :action="$viewModel->deleteUrl()"
      >
        <x-slot:trigger>
          <x-button.danger type="button" @click="open = true">{{ __('Delete reminder') }}</x-button.danger>
        </x-slot:trigger>

        {{ __('This cannot be undone. The answers you already gave stay on the contact’s reminders.') }}
      </x-modal.warning>
    </div>
  </x-panel>
</x-shell>
