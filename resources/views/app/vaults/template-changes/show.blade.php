{{-- Step three of a change of template: how far the move is, then what became of each record. --}}
{{-- @var \App\ViewModels\Vaults\TemplateChangeViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$viewModel->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" :current="$viewModel->sidebarCurrent()" />
  </x-slot:sidebar>

  <x-steps :steps="$viewModel->steps()" :current="3" />

  <x-page-header :title="$viewModel->title()" />

  <div id="template-change-result" x-merge="update" class="flex max-w-reading flex-col gap-6">
    <div class="flex flex-col gap-2.5">
      <p id="template-change-status" role="status" class="sr-only">{{ $viewModel->isRunning() ? '' : $viewModel->summary() }}</p>

      @if ($viewModel->isRunning())
        <p class="text-sm text-ink">{{ $viewModel->progressSentence() }}</p>
        <x-meter role="progressbar" :value="$viewModel->progressPercent()" :label="__('Progress of the change')" class="max-w-prose" />

        <div x-data="{ timer: null, init() { this.timer = setInterval(() => this.$ajax('{{ $viewModel->refreshUrl() }}', { target: 'template-change-result' }), 2000) }, destroy() { clearInterval(this.timer) } }"></div>
      @else
        <p class="text-xl font-semibold text-ink" aria-hidden="true">{{ $viewModel->summary() }}</p>
        <p class="text-sm text-muted">{{ $viewModel->recountSentence() }}</p>
      @endif
    </div>

    @if (! $viewModel->isRunning())
      @if ($viewModel->notMoved() !== [])
        <x-page-section :title="__('Not moved')">
          <ul class="rounded-lg border border-hairline bg-card">
            @foreach ($viewModel->notMoved() as $item)
              <li class="flex flex-col gap-1 border-b border-hairline px-4 py-2.5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                <span class="flex flex-col">
                  <span class="text-sm font-medium text-ink">{{ $item['name'] }}</span>
                  <span class="text-sm text-muted">{{ $item['reason'] }}</span>
                </span>
                <x-badge>{{ $item['outcome'] }}</x-badge>
              </li>
            @endforeach
          </ul>
        </x-page-section>
      @endif

      <p class="text-sm text-muted">{{ $viewModel->failedSentence() }}</p>

      <div class="flex flex-wrap items-center justify-end gap-3">
        <x-button :href="$viewModel->viewAffectedUrl()" type="button" variant="secondary" data-turbo="true">{{ $viewModel->viewAffectedLabel() }}</x-button>
        <x-button :href="$viewModel->backUrl()" type="button" data-turbo="true">{{ __('Back to statistics') }}</x-button>
      </div>
    @endif
  </div>
</x-shell>
