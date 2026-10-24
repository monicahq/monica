{{-- Every call an assistant made to a tool of the MCP server on this account, newest first. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\McpCallsViewModel $viewModel --}}
<x-settings.page
  :title="__('MCP history')"
  :user="$viewModel->user()"
  current="security"
  :back-href="route('settings.security.index')"
  :back-label="__('Back to security and access')"
>
  <x-page-header
    :title="__('MCP history')"
    :description="__('Every call an assistant made to your account through MCP: when it happened, what it asked for and what it got back. Nothing here can be changed or removed.')"
  />

  @if ($viewModel->hasAnyCall())
    <form method="get" action="{{ route('settings.mcpCalls.index') }}" class="flex flex-wrap items-end gap-3">
      <div class="w-full sm:w-44">
        <x-select id="period" name="period" :label="__('Period')" size="small">
          @foreach ($viewModel->periodChoices() as $value => $label)
            <option value="{{ $value }}" @selected(($viewModel->filters()['period'] ?? 'all') === $value)>{{ $label }}</option>
          @endforeach
        </x-select>
      </div>

      <div class="w-full sm:w-52">
        <x-select id="connection" name="connection" :label="__('Connection')" size="small">
          <option value="">{{ __('Every connection') }}</option>

          @foreach ($viewModel->connectionChoices() as $value => $label)
            <option value="{{ $value }}" @selected($viewModel->filters()['connection'] === $value)>{{ $label }}</option>
          @endforeach
        </x-select>
      </div>

      <div class="w-full sm:w-44">
        <x-select id="result" name="result" :label="__('Result')" size="small">
          <option value="">{{ __('Every result') }}</option>

          @foreach ($viewModel->resultChoices() as $value => $label)
            <option value="{{ $value }}" @selected($viewModel->filters()['result'] === $value)>{{ $label }}</option>
          @endforeach
        </x-select>
      </div>

      <div class="w-full sm:w-52">
        <x-select id="tool" name="tool" :label="__('Tool')" size="small">
          <option value="">{{ __('Every tool') }}</option>

          @foreach ($viewModel->toolChoices() as $tool)
            <option value="{{ $tool }}" @selected($viewModel->filters()['tool'] === $tool)>{{ $tool }}</option>
          @endforeach
        </x-select>
      </div>

      <input type="hidden" name="size" value="{{ $viewModel->size() }}">

      <x-button type="submit" size="small">{{ __('Narrow the history') }}</x-button>

      @if ($viewModel->isFiltered())
        <x-link :href="route('settings.mcpCalls.index')">{{ __('Show everything') }}</x-link>
      @endif
    </form>
  @endif

  <x-list-panel
    :items="$viewModel->entries()"
    id="mcp-calls"
    :paginator="$viewModel->entries()"
    :size="$viewModel->size()"
    :default="$viewModel->defaultSize()"
  >
    <x-slot:empty>
      @if ($viewModel->isFiltered())
        <x-empty-state :title="__('Nothing matches')" :href="route('settings.mcpCalls.index')" :link-label="__('Show everything')">
          <x-slot:icon>
            <x-icon name="search" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('No call matches these filters. Widen the period or pick another connection, result or tool.') }}
        </x-empty-state>
      @else
        <x-empty-state :title="__('No assistant has called anything yet')">
          <x-slot:icon>
            <x-icon name="timeline" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('When an assistant connected through MCP calls a tool, the call lands here, with the parameters it sent and the response it received.') }}
        </x-empty-state>
      @endif
    </x-slot:empty>

    @foreach ($viewModel->days() as $day)
      <section aria-labelledby="mcp-day-{{ $loop->index }}">
        <div class="flex items-baseline gap-2.5 border-b border-hairline bg-card px-4 py-2.5">
          <h2 id="mcp-day-{{ $loop->index }}" class="text-sm font-semibold text-ink">{{ $day['label'] }}</h2>
          <span class="text-xs text-muted">{{ $day['count'] }}</span>
        </div>

        @foreach ($day['rows'] as $row)
          @include('app.settings.account.security._mcp-call-row', ['row' => $row])
        @endforeach
      </section>
    @endforeach
  </x-list-panel>

  <div class="flex flex-col gap-1 text-sm text-muted">
    <p>{{ __('Sensitive information is masked.') }}</p>
    <p>{{ $viewModel->retention() }}</p>
  </div>
</x-settings.page>
