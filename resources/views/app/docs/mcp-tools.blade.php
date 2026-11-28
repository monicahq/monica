{{-- One family of MCP tools: what each tool does and the parameters it takes. --}}
{{--
  @var \App\ViewModels\Docs\McpDocsViewModel $viewModel
  @var array{slug: string, name: string, summary: string, tools: list<array{name: string, description: string, changesData: bool, params: list<array{name: string, type: string, required: bool, description: string, values: list<mixed>}>}>, previous: ?array{slug: string, name: string}, next: ?array{slug: string, name: string}} $family
--}}
@php
  $names = array_column($family['tools'], 'name');
  $onThisPage = array_map(fn (string $name): array => ['id' => "tool-{$name}", 'label' => $name], $names);
@endphp
<x-docs.mcp-shell :view-model="$viewModel" :crumb="$family['name']" :current="$family['slug']" :on-this-page="$onThisPage">
  <div class="flex flex-col gap-7" x-data="mcpTools({{ Js::from($names) }})" @mcp-reveal.window="reveal($event.detail)">
    <div class="flex flex-col gap-2.5">
      <x-caption tone="muted">Available tools</x-caption>
      <h1 class="text-[34px] leading-[1.15] font-semibold tracking-[-0.03em] text-ink">{{ $family['name'] }}</h1>
      <p class="text-[15px] leading-[1.6] text-ink-soft">{{ $family['summary'] }}</p>
    </div>

    <div class="flex items-center justify-between gap-3">
      <span class="text-sm text-muted">Choose a tool to see the parameters it accepts.</span>
      <button
        type="button"
        @click="toggleAll()"
        class="cursor-pointer rounded-md px-2 py-1 text-sm font-medium text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring"
        x-text="allOpen ? 'Collapse all' : 'Expand all'"
      >Expand all</button>
    </div>

    <h2 class="sr-only">Tools</h2>
    <div class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
      @foreach ($family['tools'] as $tool)
        <section
          id="tool-{{ $tool['name'] }}"
          class="scroll-mt-6 border-t border-hairline first:border-t-0"
          :class="isOpen({{ Js::from($tool['name']) }}) && 'bg-card shadow-[inset_3px_0_0_var(--color-ink)]'"
        >
          <h3 class="m-0">
            <button
              type="button"
              aria-expanded="false"
              :aria-expanded="isOpen({{ Js::from($tool['name']) }}) ? 'true' : 'false'"
              aria-controls="tool-{{ $tool['name'] }}-details"
              @click="toggle({{ Js::from($tool['name']) }})"
              class="grid w-full cursor-pointer grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-3.5 py-2.25 text-start hover:bg-hover focus-ring-inset md:grid-cols-[minmax(0,230px)_minmax(0,1fr)_auto]"
            >
              <span class="flex min-w-0 items-center gap-2">
                <code id="tool-{{ $tool['name'] }}-name" class="truncate font-mono text-[12.5px] font-semibold text-ink">{{ $tool['name'] }}</code>
              </span>
              <span
                class="hidden min-w-0 text-[13px] leading-normal text-body md:block"
                :class="isOpen({{ Js::from($tool['name']) }}) ? 'text-pretty' : 'truncate'"
              >{{ $tool['description'] }}</span>
              <span class="flex items-center gap-2 whitespace-nowrap">
                @if ($tool['changesData'])
                  <x-badge tone="warning">Changes data</x-badge>
                @endif
                <span class="text-[12.5px] text-body tabular-nums">
                  @if ($tool['params'] === [])
                    No parameters
                  @else
                    {{ count($tool['params']) === 1 ? '1 parameter' : count($tool['params']).' parameters' }}
                  @endif
                </span>
                <span class="flex text-muted transition-transform duration-(--duration-fast) ease-out motion-reduce:transition-none" :class="isOpen({{ Js::from($tool['name']) }}) && 'rotate-180'">
                  <x-icon name="chevron-down" :size="16" />
                </span>
              </span>
            </button>
          </h3>

          <div id="tool-{{ $tool['name'] }}-details" x-show="isOpen({{ Js::from($tool['name']) }})" x-cloak class="flex flex-col gap-2.5 px-3.5 pt-0.5 pb-3.5">
            <p class="text-[13.5px] leading-[1.6] text-pretty text-body md:hidden">{{ $tool['description'] }}</p>

            @if ($tool['params'] === [])
              <p class="rounded-md border border-hairline bg-canvas p-3 text-[13.5px] text-body">This tool takes no parameters. Your assistant calls it as it is.</p>
            @else
              <dl class="overflow-hidden rounded-md border border-hairline bg-canvas">
                <div class="hidden grid-cols-[minmax(0,210px)_minmax(0,1fr)] gap-4 border-b border-hairline bg-card px-3 py-2 text-[11px] font-medium tracking-[0.06em] text-muted uppercase sm:grid" aria-hidden="true">
                  <span>Parameter</span>
                  <span>Description</span>
                </div>
                @foreach ($tool['params'] as $param)
                  <div class="grid gap-x-4 gap-y-1 border-t border-hairline p-3 first:border-t-0 sm:grid-cols-[minmax(0,210px)_minmax(0,1fr)] sm:[&:nth-child(2)]:border-t-0">
                    <dt class="flex min-w-0 flex-col gap-1">
                      <code class="font-mono text-[12.5px] font-semibold break-all text-ink">{{ $param['name'] }}</code>
                      <span class="flex flex-wrap items-center gap-1.5 text-[11.5px]">
                        <code class="font-mono text-muted">{{ $param['type'] }}</code>
                        @if ($param['required'])
                          <span class="font-medium text-error">required</span>
                        @else
                          <span class="text-muted">optional</span>
                        @endif
                      </span>
                    </dt>
                    <dd class="flex flex-col gap-1.5 text-[13.5px] leading-[1.6] text-pretty text-body">
                      <span>{{ $param['description'] === '' ? 'No description' : $param['description'] }}</span>
                      @if ($param['values'] !== [])
                        <span>One of: @foreach ($param['values'] as $value)<code class="rounded-xs border border-hairline bg-card px-1 font-mono text-xs text-ink">{{ $value }}</code>@if (! $loop->last), @endif @endforeach</span>
                      @endif
                    </dd>
                  </div>
                @endforeach
              </dl>
            @endif

            <button
              type="button"
              @click="copyTool({{ Js::from($tool['name']) }}, {{ Js::from($viewModel->toolMarkdown($tool)) }})"
              class="-ms-2 flex h-7.5 self-start flex-none cursor-pointer items-center gap-1.5 rounded-md px-2 text-[12.5px] font-medium text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
            >
              <x-icon name="file" :size="14" />
              <span aria-live="polite" x-text="copyLabel({{ Js::from($tool['name']) }})">Copy as Markdown</span>
            </button>
          </div>
        </section>
      @endforeach
    </div>

    <x-docs.page-navigation
      class="pt-2"
      :previous="$family['previous'] !== null
        ? ['url' => route('docs.mcp.tools.show', $family['previous']['slug']), 'label' => $family['previous']['name']]
        : ['url' => route('docs.mcp.index'), 'label' => 'Getting started']"
      :next="$family['next'] !== null ? ['url' => route('docs.mcp.tools.show', $family['next']['slug']), 'label' => $family['next']['name']] : null"
    />
  </div>
</x-docs.mcp-shell>
