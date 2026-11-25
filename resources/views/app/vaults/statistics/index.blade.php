{{-- How many records of one kind a vault holds, on which template and version, and what is filled in. --}}
{{-- @var \App\ViewModels\Vaults\RecordStatisticsViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$viewModel->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" :current="$viewModel->sidebarCurrent()" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->scope()" />

  @include('app.vaults.statistics._overview', ['viewModel' => $viewModel])

  @include('app.vaults.statistics._templates', ['viewModel' => $viewModel])

  @include('app.vaults.statistics._field-completion', ['viewModel' => $viewModel])
</x-shell>
