{{-- Everything one vault will remind somebody about. --}}
{{-- @var \App\ViewModels\Vaults\VaultRemindersViewModel $viewModel --}}
@php
  $groups = $viewModel->groups();
  $byContact = $viewModel->groupBy() === \App\ViewModels\Vaults\VaultRemindersViewModel::GroupByContact;
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('Reminders')],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="reminders" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" />

  @include('app.vaults.reminders._tabs', [
    'activeUrl' => $viewModel->activeUrl(),
    'sentUrl' => $viewModel->sentUrl(),
    'activeCount' => $viewModel->activeCount(),
    'sentCount' => $viewModel->sentCount(),
    'historyUrl' => $viewModel->historyUrl(),
    'historyCount' => $viewModel->historyCount(),
    'current' => 'active',
  ])

  @if ($groups === [])
    <x-panel>
      <x-empty-state :title="$viewModel->emptyTitle()">
        <x-slot:icon>
          <x-icon name="reminder" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ $viewModel->emptyLine() }}

        <x-slot:action>
          <x-button :href="$viewModel->peopleUrl()" data-turbo="true" size="small">{{ __('Open your people') }}</x-button>
        </x-slot:action>
      </x-empty-state>
    </x-panel>
  @else
    <div id="reminders-toggle" x-merge="replace" class="flex items-center gap-2">
      <span id="reminders-grouping" class="text-xs text-muted">{{ __('Grouped by') }}</span>

      <div class="flex items-center gap-1.5" role="group" aria-labelledby="reminders-grouping">
        @if ($byContact)
          <x-button.ghost :href="$viewModel->groupedByWhenUrl()" size="small" x-target.push="reminders-toggle reminders-list">{{ __('When it is sent') }}</x-button.ghost>

          <x-button.secondary :href="$viewModel->groupedByContactUrl()" size="small" aria-current="true" x-target.push="reminders-toggle reminders-list">{{ __('Contact') }}</x-button.secondary>
        @else
          <x-button.secondary :href="$viewModel->groupedByWhenUrl()" size="small" aria-current="true" x-target.push="reminders-toggle reminders-list">{{ __('When it is sent') }}</x-button.secondary>

          <x-button.ghost :href="$viewModel->groupedByContactUrl()" size="small" x-target.push="reminders-toggle reminders-list">{{ __('Contact') }}</x-button.ghost>
        @endif
      </div>
    </div>

    <div id="reminders-list" x-merge="replace" class="flex flex-col gap-9 dim-when-busy">
      @foreach ($groups as $group)
        <section
          @class([
            'relative flex flex-col gap-3.5 rounded-xl',
            'border border-warning-edge bg-warning-tint' => $group['isOverdue'],
          ])
        >
          <h2 class="flex items-baseline gap-2.5">
            @if ($group['isOverdue'])
              <x-icon name="clock" :size="14" :stroke-width="1.6" class="shrink-0 self-center text-warning" />
            @endif

            <span
              @class([
                'text-2xs font-semibold tracking-caption uppercase',
                'text-warning' => $group['isOverdue'],
                'text-muted' => ! $group['isOverdue'],
              ])
            >{{ $group['title'] }}</span>

            <span class="text-xs text-muted-soft">{{ $group['count'] }}</span>
          </h2>

          <div class="flex flex-col gap-2.5">
            @foreach ($group['rows'] as $row)
              @include('app.vaults.reminders.row', ['row' => $row])
            @endforeach
          </div>
        </section>
      @endforeach
    </div>
  @endif
</x-shell>
