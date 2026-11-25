{{-- The figures at the top of the statistics of one kind of record, and what stands there when there is none. --}}
{{-- @var \App\ViewModels\Vaults\RecordStatisticsViewModel $viewModel --}}
<x-page-section :title="__('Overview')">
  <dl class="grid grid-cols-1 divide-y divide-hairline rounded-lg border border-hairline bg-card sm:grid-cols-2 sm:divide-y-0 lg:grid-flow-col lg:grid-cols-none lg:auto-cols-fr lg:divide-x">
    @foreach ($viewModel->overview() as $figure)
      <div class="flex items-baseline justify-between gap-3 px-4 py-3 sm:flex-col sm:justify-start sm:gap-1">
        <dt class="text-sm text-muted">{{ $figure['label'] }}</dt>
        <dd class="text-xl font-semibold text-ink tabular-nums">{{ $figure['value'] }}</dd>
      </div>
    @endforeach
  </dl>

  @if ($viewModel->isEmpty())
    <x-empty-state :title="$viewModel->emptyTitle()">
      {{ $viewModel->emptyDescription() }}

      @if ($viewModel->addUrl() !== null)
        <x-slot:action>
          <x-button :href="$viewModel->addUrl()" type="button" variant="secondary" data-turbo="true">
            {{ $viewModel->addLabel() }}
          </x-button>
        </x-slot:action>
      @endif
    </x-empty-state>
  @endif
</x-page-section>
