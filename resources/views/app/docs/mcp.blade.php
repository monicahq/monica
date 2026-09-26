{{-- How to connect an assistant to Monica over MCP, at /docs/mcp, open to anybody. --}}
{{-- @var \App\ViewModels\Docs\McpDocsViewModel $viewModel --}}
@use('App\ViewModels\Docs\McpDocsViewModel')
@use('Illuminate\Support\Str')
@php
  $clients = $viewModel->clients();
  $server = $viewModel->serverAddress();
  $links = $viewModel->assistantLinks();
  $inline = fn (string $text): string => Str::inlineMarkdown($text, ['html_input' => 'escape']);
  $inlineCode = '[&_strong]:font-semibold [&_strong]:text-ink [&_code]:rounded-xs [&_code]:border [&_code]:border-hairline [&_code]:bg-card [&_code]:px-1.25 [&_code]:py-px [&_code]:font-mono [&_code]:text-xs [&_code]:text-ink';
  $prose = "text-[14.5px] leading-[1.7] text-pretty text-body {$inlineCode}";
  $heading = 'text-[22px] leading-[1.3] font-semibold tracking-[-0.02em] text-ink';
  $action = 'flex h-7.5 items-center gap-1.5 rounded-md px-2 text-sm font-medium text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => McpDocsViewModel::TITLE])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div
      class="flex min-h-screen flex-col"
      x-data="mcpDocs({{ Js::from([
        'instance' => $viewModel->instanceAddress(),
        'server' => $server,
        'tabs' => array_column($clients, 'key'),
        'sections' => array_column(McpDocsViewModel::SECTIONS, 'id'),
      ]) }})"
    >
      <x-docs.header />

      <div class="border-b border-hairline bg-canvas print:hidden">
        <div class="mx-auto flex h-14 max-w-390 items-center gap-5 px-4 sm:px-7">
          <x-breadcrumb :items="[
            ['label' => __('Home'), 'url' => route('home.index')],
            ['label' => __('Documentation'), 'url' => route('docs.index')],
            ['label' => 'MCP'],
          ]" />
        </div>
      </div>

      <div class="mx-auto flex w-full max-w-280 flex-1 items-start gap-16 px-4 pt-8 pb-24 sm:px-6">
        <main id="main-content" tabindex="-1" class="flex min-w-0 max-w-190 flex-1 flex-col gap-14 focus:outline-none">
          <section id="overview" class="flex scroll-mt-20 flex-col gap-5">
            <div class="flex flex-col gap-2.5">
              <h1 class="text-[34px] leading-[1.15] font-semibold tracking-[-0.03em] text-pretty text-ink">{{ McpDocsViewModel::TITLE }}</h1>
              <p class="text-[18px] leading-normal text-pretty text-ink-soft">{{ McpDocsViewModel::LEAD }}</p>
            </div>

            <div class="-ml-2 flex flex-wrap items-center gap-1 border-b border-hairline pb-4">
              <button
                type="button"
                @click="copy('page', {{ Js::from($viewModel->markdown()) }})"
                class="{{ $action }} cursor-pointer"
              >
                <x-icon name="file" :size="15" />
                <span aria-live="polite" x-text="label('page', 'Copy for LLM')">Copy for LLM</span>
              </button>

              <span aria-hidden="true" class="h-3.5 w-px bg-hairline"></span>

              <a href="{{ route('docs.mcp.markdown.index') }}" class="{{ $action }}">
                <x-icon name="text-lines" :size="15" />
                View as Markdown
              </a>

              <span aria-hidden="true" class="h-3.5 w-px bg-hairline"></span>

              <a href="{{ $links['claude'] }}" target="_blank" rel="noopener" class="{{ $action }}">
                <x-icon name="link" :size="15" />
                Open in Claude
              </a>

              <span aria-hidden="true" class="h-3.5 w-px bg-hairline"></span>

              <a href="{{ $links['chatgpt'] }}" target="_blank" rel="noopener" class="{{ $action }}">
                <x-icon name="link" :size="15" />
                Open in ChatGPT
              </a>
            </div>

            <p class="{{ $prose }}">{{ McpDocsViewModel::OVERVIEW }}</p>

            <div class="flex flex-wrap gap-5 text-[14px] font-medium">
              <a href="#setup" class="flex items-center gap-1 rounded-xs text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring">
                Set up your assistant <x-icon name="arrow-right" :size="15" />
              </a>
              <a href="#tools" class="flex items-center gap-1 rounded-xs text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring">
                Available tools <x-icon name="arrow-right" :size="15" />
              </a>
            </div>

            <div class="flex flex-col gap-1.5">
              <span id="server-address-label" class="text-sm font-medium text-ink">Server address</span>
              <div class="flex h-9 items-stretch overflow-hidden rounded-md border border-hairline bg-card">
                <span
                  aria-labelledby="server-address-label"
                  class="flex min-w-0 flex-1 items-center truncate px-3 font-mono text-sm text-ink"
                  x-text="serverAddress"
                >{{ $server }}</span>
                <button
                  type="button"
                  @click="copy('server', {{ Js::from($server) }})"
                  class="flex flex-none cursor-pointer items-center gap-1.5 border-l border-hairline bg-canvas px-3 text-sm font-medium text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset"
                >
                  <x-icon name="file" :size="15" />
                  <span aria-live="polite" x-text="label('server', 'Copy')">Copy</span>
                  <span class="sr-only">the server address</span>
                </button>
              </div>
            </div>
          </section>

          <section id="setup" aria-labelledby="setup-heading" class="flex scroll-mt-20 flex-col gap-4">
            <h2 id="setup-heading" class="{{ $heading }}">Set up Monica MCP</h2>

            @foreach (McpDocsViewModel::SETUP as $paragraph)
              <p class="{{ $prose }}">{!! $inline($paragraph) !!}</p>
            @endforeach

            <div class="mt-1 max-w-105">
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
                          <span class="font-mono text-xs text-muted">{{ $item['block']['label'] }}</span>
                          <button
                            type="button"
                            @click="copy('{{ $key }}', {{ Js::from($item['block']['code']) }})"
                            class="ml-auto cursor-pointer rounded-xs px-2 py-1 text-xs text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
                          >
                            <span aria-live="polite" x-text="label('{{ $key }}', 'Copy')">Copy</span>
                            <span class="sr-only">{{ $item['block']['label'] }}</span>
                          </button>
                        </div>
                        <div class="overflow-x-auto px-4 py-3.5">
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

          <section id="check" aria-labelledby="check-heading" class="flex scroll-mt-20 flex-col gap-4">
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

          <section id="access" aria-labelledby="access-heading" class="flex scroll-mt-20 flex-col gap-4">
            <h2 id="access-heading" class="{{ $heading }}">Manage access</h2>

            @foreach (McpDocsViewModel::ACCESS as $paragraph)
              <p class="{{ $prose }}">{!! $inline($paragraph) !!}</p>
            @endforeach

            <x-notice tone="info" :title="McpDocsViewModel::ACCESS_NOTICE['title']">{{ McpDocsViewModel::ACCESS_NOTICE['text'] }}</x-notice>
          </section>

          <section id="tools" aria-labelledby="tools-heading" class="flex scroll-mt-20 flex-col gap-4">
            <h2 id="tools-heading" class="{{ $heading }}">Available tools</h2>

            <div class="overflow-hidden rounded-lg border border-hairline bg-canvas">
              @foreach ($viewModel->toolGroups() as $group)
                <div class="flex items-center justify-between border-t border-hairline bg-card px-4 py-2 first:border-t-0">
                  <x-caption tone="muted">{{ $group['name'] }}</x-caption>
                  @if ($group['changesData'])
                    <x-badge tone="warning">Changes data</x-badge>
                  @endif
                </div>

                <dl>
                  @foreach ($group['tools'] as $tool)
                    <div class="grid gap-x-5 gap-y-1 border-t border-hairline px-4 py-3 sm:grid-cols-[minmax(0,220px)_minmax(0,1fr)]">
                      <dt><code class="font-mono text-xs leading-[1.6] text-ink">{{ $tool['name'] }}</code></dt>
                      <dd class="text-base text-pretty text-body">{{ $tool['description'] }}</dd>
                    </div>
                  @endforeach
                </dl>
              @endforeach
            </div>

            @foreach ($viewModel->toolsSummary() as $paragraph)
              <p class="{{ $prose }}">{{ $paragraph }}</p>
            @endforeach
          </section>

          <section id="troubleshooting" aria-labelledby="troubleshooting-heading" class="flex scroll-mt-20 flex-col gap-4">
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

          <section id="see-also" aria-labelledby="see-also-heading" class="flex scroll-mt-20 flex-col gap-3 border-t border-hairline pt-8">
            <h2 id="see-also-heading" class="text-section-title text-ink">See also</h2>

            <ul role="list" class="flex flex-col gap-2 text-[14px]">
              @foreach ($viewModel->seeAlso() as $link)
                <li>
                  <a href="{{ $link['url'] }}" class="rounded-xs text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring">{{ $link['label'] }}</a>
                </li>
              @endforeach
            </ul>
          </section>
        </main>

        <aside aria-labelledby="on-this-page" class="sticky top-22 hidden w-50 flex-none flex-col gap-2.5 pt-24 lg:flex">
          <x-caption id="on-this-page" tone="muted">On this page</x-caption>

          <nav aria-labelledby="on-this-page" class="flex flex-col gap-0.5 border-l border-hairline">
            @foreach (McpDocsViewModel::SECTIONS as $section)
              <a
                href="#{{ $section['id'] }}"
                :aria-current="current === '{{ $section['id'] }}' ? 'location' : null"
                class="-ml-px border-l border-transparent py-1.25 pl-3 text-sm leading-[1.4] text-muted transition-colors duration-(--duration-fast) ease-out hover:text-ink focus-ring aria-[current]:border-ink aria-[current]:font-semibold aria-[current]:text-ink"
              >{{ $section['label'] }}</a>
            @endforeach
          </nav>
        </aside>
      </div>
    </div>
  </body>
</html>
