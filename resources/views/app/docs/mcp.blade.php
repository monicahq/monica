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

            <div class="-ml-2 flex flex-wrap items-center gap-1 border-b border-hairline pb-2">
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
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="size-3.75 shrink-0"><path d="M22.27 19.385H1.73A1.73 1.73 0 010 17.655V6.345a1.73 1.73 0 011.73-1.73h20.54A1.73 1.73 0 0124 6.345v11.308a1.73 1.73 0 01-1.73 1.731zM5.769 15.923v-4.5l2.308 2.885 2.307-2.885v4.5h2.308V8.078h-2.308l-2.307 2.885-2.308-2.885H3.46v7.847zM21.232 12h-2.309V8.077h-2.307V12h-2.308l3.461 4.039z" /></svg>
                View as Markdown
              </a>

              <span aria-hidden="true" class="h-3.5 w-px bg-hairline"></span>

              <a href="{{ $links['claude'] }}" target="_blank" rel="noopener" class="{{ $action }}">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="size-3.75 shrink-0"><path d="m4.7144 15.9555 4.7174-2.6471.079-.2307-.079-.1275h-.2307l-.7893-.0486-2.6956-.0729-2.3375-.0971-2.2646-.1214-.5707-.1215-.5343-.7042.0546-.3522.4797-.3218.686.0608 1.5179.1032 2.2767.1578 1.6514.0972 2.4468.255h.3886l.0546-.1579-.1336-.0971-.1032-.0972L6.973 9.8356l-2.55-1.6879-1.3356-.9714-.7225-.4918-.3643-.4614-.1578-1.0078.6557-.7225.8803.0607.2246.0607.8925.686 1.9064 1.4754 2.4893 1.8336.3643.3035.1457-.1032.0182-.0728-.164-.2733-1.3539-2.4467-1.445-2.4893-.6435-1.032-.17-.6194c-.0607-.255-.1032-.4674-.1032-.7285L6.287.1335 6.6997 0l.9957.1336.419.3642.6192 1.4147 1.0018 2.2282 1.5543 3.0296.4553.8985.2429.8318.091.255h.1579v-.1457l.1275-1.706.2368-2.0947.2307-2.6957.0789-.7589.3764-.9107.7468-.4918.5828.2793.4797.686-.0668.4433-.2853 1.8517-.5586 2.9021-.3643 1.9429h.2125l.2429-.2429.9835-1.3053 1.6514-2.0643.7286-.8196.85-.9046.5464-.4311h1.0321l.759 1.1293-.34 1.1657-1.0625 1.3478-.8804 1.1414-1.2628 1.7-.7893 1.36.0729.1093.1882-.0183 2.8535-.607 1.5421-.2794 1.8396-.3157.8318.3886.091.3946-.3278.8075-1.967.4857-2.3072.4614-3.4364.8136-.0425.0304.0486.0607 1.5482.1457.6618.0364h1.621l3.0175.2247.7892.522.4736.6376-.079.4857-1.2142.6193-1.6393-.3886-3.825-.9107-1.3113-.3279h-.1822v.1093l1.0929 1.0686 2.0035 1.8092 2.5075 2.3314.1275.5768-.3218.4554-.34-.0486-2.2039-1.6575-.85-.7468-1.9246-1.621h-.1275v.17l.4432.6496 2.3436 3.5214.1214 1.0807-.17.3521-.6071.2125-.6679-.1214-1.3721-1.9246L14.38 17.959l-1.1414-1.9428-.1397.079-.674 7.2552-.3156.3703-.7286.2793-.6071-.4614-.3218-.7468.3218-1.4753.3886-1.9246.3157-1.53.2853-1.9004.17-.6314-.0121-.0425-.1397.0182-1.4328 1.9672-2.1796 2.9446-1.7243 1.8456-.4128.164-.7164-.3704.0667-.6618.4008-.5889 2.386-3.0357 1.4389-1.882.929-1.0868-.0062-.1579h-.0546l-6.3385 4.1164-1.1293.1457-.4857-.4554.0608-.7467.2307-.2429 1.9064-1.3114Z" /></svg>
                Open in Claude
              </a>

              <span aria-hidden="true" class="h-3.5 w-px bg-hairline"></span>

              <a href="{{ $links['chatgpt'] }}" target="_blank" rel="noopener" class="{{ $action }}">
                <svg viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd" aria-hidden="true" class="size-3.75 shrink-0"><path d="M9.205 8.658v-2.26c0-.19.072-.333.238-.428l4.543-2.616c.619-.357 1.356-.523 2.117-.523 2.854 0 4.662 2.212 4.662 4.566 0 .167 0 .357-.024.547l-4.71-2.759a.797.797 0 00-.856 0l-5.97 3.473zm10.609 8.8V12.06c0-.333-.143-.57-.429-.737l-5.97-3.473 1.95-1.118a.433.433 0 01.476 0l4.543 2.617c1.309.76 2.189 2.378 2.189 3.948 0 1.808-1.07 3.473-2.76 4.163zM7.802 12.703l-1.95-1.142c-.167-.095-.239-.238-.239-.428V5.899c0-2.545 1.95-4.472 4.591-4.472 1 0 1.927.333 2.712.928L8.23 5.067c-.285.166-.428.404-.428.737v6.898zM12 15.128l-2.795-1.57v-3.33L12 8.658l2.795 1.57v3.33L12 15.128zm1.796 7.23c-1 0-1.927-.332-2.712-.927l4.686-2.712c.285-.166.428-.404.428-.737v-6.898l1.974 1.142c.167.095.238.238.238.428v5.233c0 2.545-1.974 4.472-4.614 4.472zm-5.637-5.303l-4.544-2.617c-1.308-.761-2.188-2.378-2.188-3.948A4.482 4.482 0 014.21 6.327v5.423c0 .333.143.571.428.738l5.947 3.449-1.95 1.118a.432.432 0 01-.476 0zm-.262 3.9c-2.688 0-4.662-2.021-4.662-4.519 0-.19.024-.38.047-.57l4.686 2.71c.286.167.571.167.856 0l5.97-3.448v2.26c0 .19-.07.333-.237.428l-4.543 2.616c-.619.357-1.356.523-2.117.523zm5.899 2.83a5.947 5.947 0 005.827-4.756C22.287 18.339 24 15.84 24 13.296c0-1.665-.713-3.282-1.998-4.448.119-.5.19-.999.19-1.498 0-3.401-2.759-5.947-5.946-5.947-.642 0-1.26.095-1.88.31A5.962 5.962 0 0010.205 0a5.947 5.947 0 00-5.827 4.757C1.713 5.447 0 7.945 0 10.49c0 1.666.713 3.283 1.998 4.448-.119.5-.19 1-.19 1.499 0 3.401 2.759 5.946 5.946 5.946.642 0 1.26-.095 1.88-.309a5.96 5.96 0 004.162 1.713z" /></svg>
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

            <x-docs.steps class="mt-2">
              @foreach (McpDocsViewModel::ACCESS as $paragraph)
                <x-docs.step>
                  <p class="{{ $prose }}">{!! $inline($paragraph) !!}</p>
                </x-docs.step>
              @endforeach
            </x-docs.steps>

            <x-docs.notice :title="McpDocsViewModel::ACCESS_NOTICE['title']">{{ McpDocsViewModel::ACCESS_NOTICE['text'] }}</x-docs.notice>
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

          <section id="templates" aria-labelledby="templates-heading" class="flex scroll-mt-20 flex-col gap-4">
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

        <aside aria-labelledby="on-this-page" class="sticky top-22 hidden w-50 flex-none flex-col gap-2.5 lg:flex">
          <x-docs.page-meta
            class="mb-4"
            :updated="$viewModel->lastUpdated()"
            :edit-url="$viewModel->editUrl()"
            :issue-url="$viewModel->issueUrl()"
          />

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
