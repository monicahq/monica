{{-- The frame of every MCP documentation page: the bar, the tool search and the families. --}}
{{--
  @var \App\ViewModels\Docs\McpDocsViewModel $viewModel
  @var string $crumb
  @var string|null $current
  @var list<array{id: string, label: string}> $onThisPage
--}}
@props(['viewModel', 'crumb', 'current' => null, 'onThisPage' => []])
@php
  $families = $viewModel->families();
  $haystack = fn (array $tool): string => mb_strtolower("{$tool['name']} {$tool['description']}");
  $navLink = 'flex items-center gap-2 rounded-md px-2.5 py-1.75 text-nav text-body no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring aria-[current=page]:bg-hover aria-[current=page]:font-medium aria-[current=page]:text-ink';
  $toolLink = 'block truncate rounded-md px-2.5 py-1.5 font-mono text-xs text-body no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring';
@endphp
<!DOCTYPE html>
<html lang="en">
  <head>
    @include('partials.meta', ['title' => "{$crumb} · MCP", 'description' => \App\ViewModels\Docs\McpDocsViewModel::LEAD])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div
      class="flex min-h-screen flex-col"
      x-data="mcpToolSearch"
    >
      <x-docs.header />

      <div class="border-b border-hairline bg-canvas print:hidden">
        <div class="flex h-12 items-center gap-5 px-4 sm:px-7">
          <x-breadcrumb :items="[
            ['label' => __('Home page'), 'url' => route('home.index')],
            ['label' => __('Documentation'), 'url' => route('docs.index')],
            ['label' => 'MCP', 'url' => route('docs.mcp.index')],
            ['label' => $crumb],
          ]" />
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-4 border-b border-hairline bg-canvas px-4 py-2.5 sm:px-7 print:hidden">
        <div class="flex items-center gap-2">
          <x-icon name="link" :size="15" class="text-muted" />
          <span class="text-[15px] font-semibold text-ink">MCP</span>
          <span class="rounded-xs border border-hairline bg-card px-1.5 py-px font-mono text-[11px] text-muted">{{ $viewModel->toolCount() }} tools</span>
        </div>

        <label class="flex h-8.5 max-w-95 min-w-0 flex-[1_1_260px] items-center gap-2 rounded-md border border-hairline bg-input px-2.5 has-focus-visible:ring-[3px] has-focus-visible:ring-focus-ring/45">
          <x-icon name="search" :size="15" class="flex-none text-muted" />
          <span class="sr-only">Search tools</span>
          <input
            type="search"
            placeholder="Search tools"
            x-model="query"
            @keydown.escape="query = ''"
            aria-controls="mcp-tool-search-results"
            class="min-w-0 flex-1 border-0 bg-transparent text-nav text-ink outline-none placeholder:text-muted"
          >
        </label>
        <p role="status" class="sr-only" x-text="status"></p>

        <div class="ms-auto flex gap-2">
          <x-button variant="secondary" :href="route('docs.mcp.markdown.index')">View as Markdown</x-button>
          <x-button variant="secondary" :href="route('settings.security.index')">Get an API key</x-button>
        </div>
      </div>

      <div class="grid flex-1 items-start gap-x-8 md:grid-cols-[272px_minmax(0,1fr)] xl:grid-cols-[272px_minmax(0,1fr)_248px]">
        <aside
          class="hidden self-stretch border-hairline px-4 pt-7 pb-12 md:block md:border-e print:hidden"
          :class="searching && '!block'"
        >
          <nav aria-label="MCP documentation" class="flex flex-col gap-7">
            <div class="flex flex-col gap-1.5">
              <x-caption tone="muted" class="px-2.5">Getting started</x-caption>
              <a href="{{ route('docs.mcp.index') }}" data-turbo="true" @if ($current === null) aria-current="page" @endif class="{{ $navLink }}">Getting started</a>
            </div>

            <div class="flex flex-col gap-1.5">
              <x-caption tone="muted" class="px-2.5">Available tools</x-caption>

              <div id="mcp-tool-search-results" x-show="searching" x-cloak class="flex flex-col gap-3.5 pt-1">
                @foreach ($families as $family)
                  <div class="flex flex-col gap-0.5" x-show="searching && [...$el.querySelectorAll('[data-search]')].some((link) => matches(link.dataset.search))">
                    <span class="px-2.5 pb-1 text-sm font-semibold text-ink">{{ $family['name'] }}</span>
                    @foreach ($family['tools'] as $tool)
                      <a
                        href="{{ route('docs.mcp.tools.show', $family['slug']) }}#tool-{{ $tool['name'] }}"
                        data-turbo="true"
                        data-search="{{ $haystack($tool) }}"
                        x-show="matches($el.dataset.search)"
                        class="{{ $toolLink }}"
                      >{{ $tool['name'] }}</a>
                    @endforeach
                  </div>
                @endforeach

                <p x-show="! anyMatch" class="px-2.5 text-sm leading-normal text-muted">
                  No tool matches “<span x-text="query.trim()"></span>”. Try a word from what the tool does, such as reminder or note.
                </p>
              </div>

              <div x-show="! searching" class="flex flex-col gap-px">
                @foreach ($families as $family)
                  <div class="flex flex-col">
                    <a href="{{ route('docs.mcp.tools.show', $family['slug']) }}" data-turbo="true" @if ($current === $family['slug']) aria-current="page" @endif class="{{ $navLink }}">
                      <span class="min-w-0 flex-1">{{ $family['name'] }}</span>
                      <span class="text-xs text-body tabular-nums">{{ count($family['tools']) }}</span>
                    </a>

                    @if ($current === $family['slug'])
                      <div class="mt-1 mb-2 ms-4.5 flex flex-col gap-px border-s border-hairline ps-2">
                        @foreach ($family['tools'] as $tool)
                          <a href="#tool-{{ $tool['name'] }}" @click="$dispatch('mcp-reveal', '#tool-{{ $tool['name'] }}')" class="{{ $toolLink }}">{{ $tool['name'] }}</a>
                        @endforeach
                      </div>
                    @endif
                  </div>
                @endforeach
              </div>
            </div>
          </nav>
        </aside>

        <main id="main-content" tabindex="-1" class="min-w-0 px-4 pt-10 pb-30 focus:outline-none sm:px-12">
          <div class="flex max-w-190 flex-col gap-12">
            <details class="rounded-lg border border-hairline bg-card md:hidden print:hidden">
              <summary class="cursor-pointer rounded-lg px-4 py-3 text-nav font-medium text-ink focus-ring">Browse the tools</summary>
              <nav aria-label="Tool families" class="flex flex-col gap-px px-2 pb-3">
                <a href="{{ route('docs.mcp.index') }}" data-turbo="true" @if ($current === null) aria-current="page" @endif class="{{ $navLink }}">Getting started</a>
                @foreach ($families as $family)
                  <a href="{{ route('docs.mcp.tools.show', $family['slug']) }}" data-turbo="true" @if ($current === $family['slug']) aria-current="page" @endif class="{{ $navLink }}">
                    <span class="min-w-0 flex-1">{{ $family['name'] }}</span>
                    <span class="text-xs text-muted tabular-nums">{{ count($family['tools']) }}</span>
                  </a>
                @endforeach
              </nav>
            </details>

            {{ $slot }}
          </div>
        </main>

        <aside class="sticky top-0 hidden max-h-screen flex-col gap-6 self-start overflow-y-auto pt-10 pe-7 pb-12 ps-1 xl:flex print:hidden">
          <x-docs.page-meta
            :updated="$viewModel->lastUpdated()"
            :edit-url="$viewModel->editUrl()"
            :issue-url="$viewModel->issueUrl()"
          />

          <div class="flex flex-col gap-2.5">
            <x-caption id="on-this-page" tone="muted">On this page</x-caption>
            <nav aria-labelledby="on-this-page" class="flex flex-col gap-0.5 border-s border-hairline">
              @foreach ($onThisPage as $entry)
                <a href="#{{ $entry['id'] }}" class="-ms-px border-s border-transparent py-1.25 ps-3 text-sm leading-[1.4] break-words text-muted no-underline transition-colors duration-(--duration-fast) ease-out hover:text-ink focus-ring">{{ $entry['label'] }}</a>
              @endforeach
            </nav>
          </div>
        </aside>
      </div>
    </div>
  </body>
</html>
