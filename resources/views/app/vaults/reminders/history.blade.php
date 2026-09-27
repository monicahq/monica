{{-- What was answered in this vault, the latest answer first. --}}
{{-- @var \App\ViewModels\Vaults\VaultRemindersHistoryViewModel $viewModel --}}
@php
  $rows = $viewModel->rows();
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
    'current' => 'history',
  ])

  <x-list-panel
    :items="$rows"
    id="history-rows"
    merge="append"
    target="history-rows history-pagination"
    footer-id="history-pagination"
    :next-page-url="$viewModel->hasMorePages() ? $viewModel->nextPageUrl() : null"
  >
    <x-slot:empty>
      <x-empty-state :title="$viewModel->emptyTitle()">
        <x-slot:icon>
          <x-icon name="reminder" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ $viewModel->emptyLine() }}
      </x-empty-state>
    </x-slot:empty>

    @foreach ($rows as $row)
      @include('app.vaults.reminders._history-row', ['row' => $row])
    @endforeach
  </x-list-panel>
</x-shell>
