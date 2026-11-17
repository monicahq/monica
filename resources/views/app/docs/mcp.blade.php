{{-- How to connect an assistant to Monica over MCP, at /docs/mcp, open to anybody. --}}
{{-- @var \App\ViewModels\Docs\McpDocsViewModel $viewModel --}}
@use('App\ViewModels\Docs\McpDocsViewModel')
@use('Illuminate\Support\Str')
@php
  $clients = $viewModel->clients();
  $server = $viewModel->serverAddress();
  $inline = fn (string $text): string => Str::inlineMarkdown($text, ['html_input' => 'escape']);
  $inlineCode = '[&_strong]:font-semibold [&_strong]:text-ink [&_code]:rounded-xs [&_code]:border [&_code]:border-hairline [&_code]:bg-card [&_code]:px-1.25 [&_code]:py-px [&_code]:font-mono [&_code]:text-xs [&_code]:text-ink';
  $prose = "text-[14.5px] leading-[1.7] text-pretty text-body {$inlineCode}";
  $heading = 'text-[22px] leading-[1.3] font-semibold tracking-[-0.02em] text-ink';
@endphp
<x-docs.mcp-shell :view-model="$viewModel" crumb="Getting started" :on-this-page="McpDocsViewModel::SECTIONS">
  <div
    class="flex flex-col gap-12"
    x-data="mcpDocs({{ Js::from([
      'instance' => $viewModel->instanceAddress(),
      'server' => $server,
      'tabs' => array_column($clients, 'key'),
      'sections' => array_column(McpDocsViewModel::SECTIONS, 'id'),
    ]) }})"
  >
    <section id="overview" class="flex scroll-mt-6 flex-col gap-5">
      <div class="flex flex-col gap-2.5">
        <h1 class="text-[34px] leading-[1.15] font-semibold tracking-[-0.03em] text-pretty text-ink">{{ McpDocsViewModel::TITLE }}</h1>
        <p class="text-[18px] leading-normal text-pretty text-ink-soft">{{ McpDocsViewModel::LEAD }}</p>
      </div>

      <p class="{{ $prose }}">{{ McpDocsViewModel::OVERVIEW }}</p>

      <div class="flex flex-col gap-1.5">
        <span id="server-address-label" class="text-sm font-medium text-ink">Server address</span>
        <div class="flex min-h-9 items-stretch overflow-hidden rounded-md border border-hairline bg-card">
          <code
            class="flex min-w-0 flex-1 items-center px-3 py-2 font-mono text-sm break-all text-ink"
            x-text="serverAddress"
          >{{ $server }}</code>
          <button
            type="button"
            @click="copy('server', {{ Js::from($server) }})"
            class="flex flex-none cursor-pointer items-center gap-1.5 border-l border-hairline bg-canvas px-3 text-sm font-medium text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset"
          >
            <x-icon name="file" :size="15" />
            <span>Copy</span>
            <span class="sr-only">the server address</span>
          </button>
        </div>
        <p role="status" aria-live="polite" class="text-sm text-body empty:hidden" x-text="label('server', '')"></p>
      </div>
    </section>

    <section id="setup" aria-labelledby="setup-heading" class="flex scroll-mt-6 flex-col gap-4">
      <h2 id="setup-heading" class="{{ $heading }}">Set up Monica MCP</h2>

      <x-docs.steps class="mt-2">
        @foreach (McpDocsViewModel::SETUP as $paragraph)
          <x-docs.step>
            <p class="{{ $prose }}">{!! $inline($paragraph) !!}</p>
          </x-docs.step>
        @endforeach

        <x-docs.step>
          <div class="max-w-105">
            <x-input
              id="instance-address"
              type="url"
              label="Your Monica address"
              :value="$viewModel->instanceAddress()"
              help="Every example on this page uses this address."
              spellcheck="false"
              x-model="address"
            />
          </div>
        </x-docs.step>
      </x-docs.steps>

      <div class="mt-3 flex flex-col gap-5">
        <div role="tablist" aria-label="Assistant" class="flex flex-wrap items-end gap-0.5 border-b border-hairline" @keydown="move($event)">
          @foreach ($clients as $client)
            <button
              type="button"
              role="tab"
              id="client-tab-{{ $client['key'] }}"
              aria-controls="client-panel-{{ $client['key'] }}"
              aria-selected="{{ $loop->first ? 'true' : 'false' }}"
              tabindex="{{ $loop->first ? '0' : '-1' }}"
              :aria-selected="tab === '{{ $client['key'] }}' ? 'true' : 'false'"
              :tabindex="tab === '{{ $client['key'] }}' ? '0' : '-1'"
              @click="select('{{ $client['key'] }}')"
              class="-mb-px flex h-9 flex-none cursor-pointer items-center border-b-2 border-transparent px-2.5 text-nav whitespace-nowrap text-muted transition-colors duration-(--duration-fast) ease-out hover:text-ink focus-ring aria-selected:border-b-primary aria-selected:font-semibold aria-selected:text-ink"
            >{{ $client['label'] }}</button>
          @endforeach
        </div>

        @foreach ($clients as $client)
          <div
            role="tabpanel"
            id="client-panel-{{ $client['key'] }}"
            aria-labelledby="client-tab-{{ $client['key'] }}"
            tabindex="0"
            @unless ($loop->first) hidden @endunless
            :hidden="tab !== '{{ $client['key'] }}'"
            class="flex flex-col gap-4 rounded-xs focus-ring"
          >
            @foreach ($client['content'] as $item)
              @if ($item['block'] !== null)
                @php
                  $key = $client['key'].'-'.$loop->index;
                @endphp
                <div class="overflow-hidden rounded-lg border border-hairline bg-sunken">
                  <div class="flex items-center gap-2 border-b border-hairline-soft bg-hover px-3 py-2">
                    <span class="font-mono text-xs text-body">{{ $item['block']['label'] }}</span>
                    <button
                      type="button"
                      @click="copy('{{ $key }}', {{ Js::from($item['block']['code']) }})"
                      class="ml-auto cursor-pointer rounded-xs px-2 py-1 text-xs text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
                    >
                      <span>Copy</span>
                      <span class="sr-only">{{ $item['block']['label'] }}</span>
                    </button>
                  </div>
                  <p role="status" aria-live="polite" class="px-4 pt-2 text-sm text-body empty:hidden" x-text="label('{{ $key }}', '')"></p>
                  <div tabindex="0" role="region" aria-label="{{ $item['block']['label'] }}" class="overflow-x-auto px-4 py-3.5 focus-ring-inset">
                    @foreach ($item['block']['lines'] as $line)
                      <div class="font-mono text-xs leading-[1.75] whitespace-pre">@foreach ($line['parts'] as $part)<span class="{{ $part['colour'] === 'brand' ? 'text-ink' : 'text-muted' }}" @if (str_contains($part['text'], $server)) x-text="swap({{ Js::from($part['text']) }})" @endif>{{ $part['text'] }}</span>@endforeach</div>
                    @endforeach
                  </div>
                </div>
              @elseif ($item['type'] === 'notice')
                <x-notice :tone="$item['tone']" :title="$item['title']" :class="$inlineCode">{!! $inline($item['text']) !!}</x-notice>
              @else
                <p class="{{ $prose }}">{!! $inline($item['text']) !!}</p>
              @endif
            @endforeach
          </div>
        @endforeach
      </div>
    </section>

    <section id="check" aria-labelledby="check-heading" class="flex scroll-mt-6 flex-col gap-4">
      <h2 id="check-heading" class="{{ $heading }}">Check your connection</h2>
      <p class="{{ $prose }}">Ask your assistant:</p>
      <ul role="list" class="flex flex-col overflow-hidden rounded-lg border border-hairline">
        @foreach (McpDocsViewModel::CHECKS as $check)
          <li class="flex flex-col gap-1 border-t border-hairline px-4 py-3.5 first:border-t-0">
            <span class="text-[15px] font-semibold text-ink">{{ $check['prompt'] }}</span>
            <span class="text-base text-body">{{ $check['note'] }}</span>
          </li>
        @endforeach
      </ul>
    </section>

    <section id="access" aria-labelledby="access-heading" class="flex scroll-mt-6 flex-col gap-4">
      <h2 id="access-heading" class="{{ $heading }}">Manage access</h2>

      <x-docs.steps class="mt-2">
        @foreach (McpDocsViewModel::ACCESS as $paragraph)
          <x-docs.step>
            <p class="{{ $prose }}">{!! $inline($paragraph) !!}</p>
          </x-docs.step>
        @endforeach
      </x-docs.steps>

      <x-docs.notice :title="McpDocsViewModel::ACCESS_NOTICE['title']">{{ McpDocsViewModel::ACCESS_NOTICE['text'] }}</x-docs.notice>
    </section>

    <section id="templates" aria-labelledby="templates-heading" class="flex scroll-mt-6 flex-col gap-4">
      <h2 id="templates-heading" class="{{ $heading }}">Edit a template</h2>

      @foreach (McpDocsViewModel::TEMPLATES as $paragraph)
        <p class="{{ $prose }}">{{ $paragraph }}</p>
      @endforeach

      <p class="{{ $prose }}">{{ McpDocsViewModel::TEMPLATE_EXAMPLE }}</p>

      <ol class="list-decimal space-y-1.5 pl-5 {{ $prose }}">
        @foreach (McpDocsViewModel::TEMPLATE_STEPS as $step)
          <li>{!! $inline($step) !!}</li>
        @endforeach
      </ol>
    </section>

    <section id="troubleshooting" aria-labelledby="troubleshooting-heading" class="flex scroll-mt-6 flex-col gap-4">
      <h2 id="troubleshooting-heading" class="{{ $heading }}">Troubleshooting</h2>

      <dl>
        @foreach ($viewModel->troubleshooting() as $entry)
          <div class="grid gap-x-5 gap-y-1 border-t border-hairline py-3.5 sm:grid-cols-[minmax(0,220px)_minmax(0,1fr)]">
            <dt class="text-[14px] leading-[1.6] font-semibold text-ink">{{ $entry['title'] }}</dt>
            <dd class="{{ $prose }} text-[14px]">{!! $inline($entry['text']) !!}</dd>
          </div>
        @endforeach
      </dl>
    </section>

    <section id="see-also" aria-labelledby="see-also-heading" class="flex scroll-mt-6 flex-col gap-3 border-t border-hairline pt-8">
      <h2 id="see-also-heading" class="text-section-title text-ink">See also</h2>

      <ul role="list" class="flex flex-col gap-2 text-[14px]">
        @foreach ($viewModel->seeAlso() as $link)
          <li>
            <a href="{{ $link['url'] }}" class="rounded-xs text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring">{{ $link['label'] }}</a>
          </li>
        @endforeach
      </ul>
    </section>
    @if ($viewModel->families() !== [])
      <x-docs.page-navigation
        class="pt-2"
        :next="['url' => route('docs.mcp.tools.show', $viewModel->families()[0]['slug']), 'label' => $viewModel->families()[0]['name']]"
      />
    @endif
  </div>
</x-docs.mcp-shell>
