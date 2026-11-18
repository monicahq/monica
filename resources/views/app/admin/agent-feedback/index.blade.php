{{-- The reports AI agents sent about the MCP server, by status, with the filters over them. --}}
{{-- @var \App\ViewModels\Admin\AgentFeedbackQueueViewModel $viewModel --}}
@php
  $queue = $viewModel->queue();
  $rows = $viewModel->rows();
@endphp

<x-admin-layout :title="__('Agent feedback')">
  <x-page-header
    :title="__('Agent feedback')"
    :description="__('Problems AI agents met with the MCP server while working in somebody’s account, each with what was asked, what the agent tried and the fix it suggests.')"
  />

  <x-tabs :aria-label="__('Status')">
    @foreach ($viewModel->statuses() as $status)
      <x-tabs.item :href="$status['url']" :selected="$status['selected']" :count="$status['count']">{{ $status['label'] }}</x-tabs.item>
    @endforeach
  </x-tabs>

  <form method="get" action="{{ route('admin.agentFeedback.index') }}" class="flex flex-wrap items-end gap-2" role="search" aria-label="{{ __('Filter reports') }}">
    <input type="hidden" name="status" value="{{ $queue->status }}" />

    <div class="min-w-55 flex-[1_1_260px]">
      <x-input type="search" id="q" name="q" size="small" :label="__('Search')" :value="$queue->search" :placeholder="__('Title, tool, name or email')" />
    </div>

    <x-select id="kind" name="kind" size="small" :label="__('Kind')">
      @foreach ($viewModel->kindOptions() as $option)
        <option value="{{ $option['value'] }}" @selected($queue->kind === $option['value'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <x-select id="severity" name="severity" size="small" :label="__('Severity')">
      @foreach ($viewModel->severityOptions() as $option)
        <option value="{{ $option['value'] }}" @selected($queue->severity === $option['value'])>{{ $option['label'] }}</option>
      @endforeach
    </x-select>

    <x-button.secondary size="small">{{ __('Filter') }}</x-button.secondary>

    @if ($queue->hasFilters())
      <x-button.ghost :href="$viewModel->clearFiltersUrl()" size="small">{{ __('Clear filters') }}</x-button.ghost>
    @endif
  </form>

  <section aria-label="{{ __('Reports') }}">
    <x-list-panel
      :items="$rows"
      id="agent-feedback-reports"
      :paginator="$viewModel->reports()"
      :size="$viewModel->size()"
      :default="$viewModel->defaultSize()"
    >
      <x-slot:empty>
        <x-empty-state :title="$viewModel->emptyTitle()">
          <x-slot:icon>
            <x-icon name="memory" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ $viewModel->emptyBody() }}
        </x-empty-state>
      </x-slot:empty>

      @foreach ($rows as $row)
        @include('app.admin.agent-feedback._row', ['row' => $row])
      @endforeach
    </x-list-panel>
  </section>
</x-admin-layout>
