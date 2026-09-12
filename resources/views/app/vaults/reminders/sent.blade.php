{{-- What this vault has already told somebody about. --}}
{{-- @var \App\ViewModels\Vaults\VaultRemindersSentViewModel $viewModel --}}
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

  <x-page-header :title="$viewModel->title()" :description="$viewModel->intro()" />

  @include('app.vaults.reminders._tabs', [
    'activeUrl' => $viewModel->activeUrl(),
    'sentUrl' => $viewModel->sentUrl(),
    'activeCount' => $viewModel->activeCount(),
    'sentCount' => $viewModel->sentCount(),
    'current' => 'sent',
  ])

  <x-list-panel
    :items="$rows"
    id="sent-rows"
    merge="append"
    target="sent-rows sent-pagination"
    footer-id="sent-pagination"
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
      @include('app.vaults.reminders._sent-row', ['row' => $row])
    @endforeach
  </x-list-panel>
</x-shell>
