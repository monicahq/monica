{{-- One reminder: who it is about, when it fires, the dates waiting for an answer, and what was answered. --}}
{{-- @var \App\ViewModels\Vaults\ReminderViewModel $viewModel --}}
@php
  $contact = $viewModel->contact();
  $group = $viewModel->group();
  $problem = $viewModel->problem();
  $awaiting = $viewModel->awaiting();
  $history = $viewModel->history();
  $historyRows = $viewModel->historyRows();
  $deliveryLog = $viewModel->deliveryLog();
  $crumbs = match (true) {
    $group !== null => [
      ['label' => __('Groups'), 'url' => $group['indexUrl']],
      ['label' => $group['name'], 'url' => $group['url']],
    ],
    $contact === null => [['label' => __('Reminders'), 'url' => $viewModel->remindersUrl()]],
    default => [
        ['label' => __('People'), 'url' => route('contacts.index', $viewModel->vault())],
        ['label' => $contact['name'], 'url' => $contact['url']],
        ['label' => __('Reminders'), 'url' => $viewModel->contactRemindersUrl()],
      ],
  };
  $badges = [
    'overdue' => ['tone' => 'danger', 'label' => __('Overdue')],
    'today' => ['tone' => 'warning', 'label' => __('Today')],
    'upcoming' => ['tone' => 'neutral', 'label' => __('Upcoming')],
  ];
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[...$crumbs, ['label' => $viewModel->title()]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" :current="$group !== null ? 'groups' : ($contact === null ? 'reminders' : 'contacts')" />
  </x-slot:sidebar>

  <div class="flex flex-col gap-8">
    <div class="flex flex-wrap items-start gap-x-6 gap-y-4">
      <div class="flex min-w-70 flex-1 flex-col gap-3">
        <h1 class="text-page-title text-pretty text-ink">{{ $viewModel->title() }}</h1>

        <div class="flex flex-col gap-2 text-sm text-body">
          @if ($group !== null)
            <span class="flex items-center gap-2">
              <span class="text-muted" aria-hidden="true"><x-icon name="people" :size="16" /></span>

              <a href="{{ $group['url'] }}" data-turbo="true" class="rounded-xs font-medium focus-ring text-ink underline decoration-hairline-strong underline-offset-2 hover:decoration-ink">{{ $group['name'] }}</a>
            </span>
          @endif

          @if ($contact !== null)
            <span class="flex items-center gap-2">
              <x-contact-avatar :name="$contact['name']" :url="$contact['avatarUrl']" :size="20" />

              <a href="{{ $contact['url'] }}" data-turbo="true" class="font-medium text-ink underline decoration-hairline-strong underline-offset-2 hover:decoration-ink">{{ $contact['name'] }}</a>
            </span>
          @endif

          <span class="flex items-center gap-2">
            <x-icon name="calendar-repeat" :size="16" class="text-muted" />

            {{ $viewModel->scheduleLine() }}
          </span>

          @if ($viewModel->sourceLine() !== null)
            <span class="flex flex-wrap items-center gap-2">
              <x-icon name="link" :size="16" class="text-muted" />

              {{ $viewModel->sourceLine() }}

              @if ($viewModel->editDateUrl() !== null)
                <span aria-hidden="true" class="text-muted-soft">·</span>

                <a href="{{ $viewModel->editDateUrl() }}" data-turbo="true" class="underline decoration-hairline-strong underline-offset-2 hover:decoration-ink">{{ __('Edit the date') }}</a>
              @endif
            </span>
          @endif
        </div>
      </div>

      <div class="flex shrink-0 items-center gap-2">
        @if ($viewModel->editUrl() !== null)
          <x-button.secondary :href="$viewModel->editUrl()" data-turbo="true" size="small">{{ __('Edit') }}</x-button.secondary>
        @endif

        <x-actions-menu :label="__('More actions')">
          <x-modal.warning
            :title="__('Delete this reminder?')"
            :confirm="__('Delete reminder')"
            :action="$viewModel->deleteUrl()"
          >
            <x-slot:trigger>
              <button
                type="button"
                role="menuitem"
                @click="menuOpen = false; $refs.menuTrigger.focus(); open = true"
                class="flex w-full cursor-pointer items-center gap-2.5 rounded-xs px-2.5 py-2 text-start font-sans text-sm text-error transition-colors hover:bg-hover focus-ring-inset"
              >{{ __('Delete reminder') }}</button>
            </x-slot:trigger>

            {{ $viewModel->deleteLine() }}
          </x-modal.warning>
        </x-actions-menu>
      </div>
    </div>

    @if ($problem !== null)
      <x-notice tone="warning" :title="$problem['title']">{{ $problem['body'] }}</x-notice>
    @endif

    <section aria-labelledby="awaiting-title" class="flex flex-col gap-3">
      <h2 id="awaiting-title" class="text-base font-semibold text-ink">{{ __('Dates awaiting your response') }}</h2>

      <div class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
        @foreach ($awaiting as $entry)
          <div class="flex flex-wrap items-center gap-x-4 gap-y-2.5 border-t border-hairline px-4 py-3 first:border-t-0">
            <div class="flex min-w-55 flex-1 flex-wrap items-center gap-x-2.5 gap-y-1.5">
              <span class="font-semibold text-ink">{{ $entry['date'] }}</span>

              <span class="text-sm text-muted">{{ $entry['row']->relativeDay() }}</span>

              <x-badge :tone="$badges[$entry['category']->value]['tone']">{{ $badges[$entry['category']->value]['label'] }}</x-badge>
            </div>

            <div class="flex items-center gap-2">
              @include('app.vaults.reminders._answers', ['row' => $entry['row']])
            </div>
          </div>
        @endforeach

        @if ($problem !== null)
          <p class="border-t border-hairline px-4 py-3 text-sm text-muted first:border-t-0">{{ __('No upcoming date until this is resolved.') }}</p>
        @elseif ($awaiting === [])
          <p class="px-4 py-3 text-sm text-muted">{{ __('Nothing is waiting for an answer, and no date is left to come.') }}</p>
        @endif
      </div>
    </section>

    <section aria-labelledby="history-title" class="flex flex-col gap-3">
      <h2 id="history-title" class="text-base font-semibold text-ink">{{ __('History') }}</h2>

      @if ($historyRows === [])
        <p class="text-sm text-muted">{{ __('Each date you answer is kept here, with what you said.') }}</p>
      @else
        <x-list-panel
          :items="$historyRows"
          id="reminder-history"
          merge="append"
          target="reminder-history reminder-history-footer"
          footer-id="reminder-history-footer"
          :next-page-url="$history->hasMorePages() ? $history->nextPageUrl() : null"
        >
          @foreach ($historyRows as $answer)
            <div class="flex flex-wrap items-baseline gap-x-4 gap-y-1 border-t border-hairline px-4 py-3 first:border-t-0">
              <span class="flex min-w-55 flex-1 flex-wrap items-baseline gap-x-2 gap-y-1">
                <span class="font-semibold text-ink">{{ $answer['date'] }}</span>

                <span aria-hidden="true" class="text-muted">·</span>

                <span class="text-body">{{ $answer['outcome'] }}</span>
              </span>

              @if ($answer['answered'] !== null)
                <span class="text-sm text-muted">{{ $answer['answered'] }}</span>
              @endif
            </div>
          @endforeach

          <x-slot:empty></x-slot:empty>
        </x-list-panel>
      @endif
    </section>

    @if ($deliveryLog->isNotEmpty())
      @include('app.vaults.reminders._history', ['entries' => $deliveryLog, 'showsEnd' => $viewModel->deliveryPage() > 1])
    @endif
  </div>
</x-shell>
