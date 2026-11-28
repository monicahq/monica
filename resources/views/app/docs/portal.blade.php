{{-- One page of the product guide, at /docs/{locale}, open to anybody, with the list of pages beside it. --}}
{{-- @var \App\ViewModels\Docs\PortalDocsViewModel $viewModel --}}
@php
  $page = $viewModel->page;
  $root = $viewModel->root();
  $previous = $viewModel->previous();
  $next = $viewModel->next();
  $inlineCode = '[&_strong]:font-semibold [&_strong]:text-ink [&_code]:rounded-xs [&_code]:border [&_code]:border-hairline [&_code]:bg-card [&_code]:px-1.25 [&_code]:py-px [&_code]:font-mono [&_code]:text-xs [&_code]:text-ink';
  $linkStyle = '[&_a]:rounded-xs [&_a]:text-ink [&_a]:underline [&_a]:decoration-hairline-strong [&_a]:underline-offset-3 [&_a:hover]:decoration-ink';
  $prose = "text-[14.5px] leading-[1.7] text-pretty text-body {$inlineCode} {$linkStyle}";
  $blockProse = "{$prose} [&>*+*]:mt-4 [&_h2]:mt-10 [&_h2]:scroll-mt-6 [&_h2]:text-[22px] [&_h2]:leading-[1.3] [&_h2]:font-semibold [&_h2]:tracking-[-0.02em] [&_h2]:text-ink [&_[data-heading-anchor]]:ml-2 [&_[data-heading-anchor]]:px-0.5 [&_[data-heading-anchor]]:font-normal [&_[data-heading-anchor]]:text-muted [&_[data-heading-anchor]]:no-underline [&_[data-heading-anchor]]:opacity-0 [&_[data-heading-anchor]]:transition-opacity [&_[data-heading-anchor]]:duration-(--duration-fast) [&_h2:hover_[data-heading-anchor]]:opacity-100 [&_[data-heading-anchor]:focus-visible]:opacity-100 [&_[data-heading-anchor]:hover]:text-ink [&_h3]:mt-8 [&_h3]:scroll-mt-6 [&_h3]:text-[17px] [&_h3]:font-semibold [&_h3]:text-ink [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_li+li]:mt-1.5 [&_table]:w-full [&_table]:text-[14px] [&_th]:border-b [&_th]:border-hairline [&_th]:py-2 [&_th]:pr-4 [&_th]:text-left [&_th]:font-semibold [&_th]:text-ink [&_td]:border-b [&_td]:border-hairline [&_td]:py-2 [&_td]:pr-4 [&_td]:align-top [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:border [&_pre]:border-hairline [&_pre]:bg-sunken [&_pre]:px-4 [&_pre]:py-3.5 [&_pre_code]:border-0 [&_pre_code]:bg-transparent [&_pre_code]:p-0";
  $image = 'h-auto max-h-130 w-auto max-w-full self-start rounded-lg border border-hairline bg-canvas shadow-panel';
  $placeholder = 'grid aspect-video place-items-center rounded-lg border border-hairline bg-card px-6 text-center text-sm text-muted shadow-panel';
  $entry = 'flex min-h-8 items-center rounded-md px-3 py-1.5 text-[13.5px] leading-snug transition-colors duration-(--duration-fast) ease-out focus-ring';
  $entryIdle = 'text-body hover:bg-hover hover:text-ink';
  $entryCurrent = 'bg-canvas font-semibold text-ink shadow-nav-active';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $viewModel->locale()) }}" dir="{{ \App\Helpers\AvailableLocales::direction($viewModel->locale()) }}">
  <head>
    @include('partials.meta', [
      'title' => $page->title,
      'description' => $page->description,
    ])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div
      class="flex min-h-screen flex-col"
      x-data="portalDocs({{ Js::from(['sections' => array_column($viewModel->contents(), 'id')]) }})"
    >
      <x-docs.header />

      <div class="flex flex-1 flex-col lg:flex-row">
        <nav
          id="portal-pages"
          aria-label="Documentation"
          :data-open="open ? '' : null"
          class="hidden w-full shrink-0 border-b border-hairline bg-card px-4 pt-5 pb-8 data-open:block lg:block lg:w-65 lg:border-r lg:border-b-0 print:hidden"
        >
          <label class="flex h-9 items-center gap-2 rounded-md border border-hairline-strong bg-canvas px-3 text-muted focus-within:ring-[3px] focus-within:ring-focus-ring/45">
            <x-icon name="search" :size="15" />
            <input
              type="search"
              x-model="query"
              placeholder="Search documentation"
              aria-label="Search documentation"
              class="min-w-0 flex-1 border-0 bg-transparent p-0 text-[13.5px] text-ink outline-none placeholder:text-placeholder"
            >
          </label>

          <div class="mt-6 flex flex-col gap-1.5">
            @if ($root !== null)
              <a
                href="{{ $root->url() }}"
                x-show="matches({{ Js::from(mb_strtolower($root->title)) }})"
                @if ($root === $page) aria-current="page" @endif
                class="{{ $entry }} {{ $root === $page ? $entryCurrent : $entryIdle }}"
              >{{ $root->title }}</a>
            @endif

            @foreach ($viewModel->sections() as $section)
              @php
                $titles = array_map(fn ($item): string => mb_strtolower($item->title), [$section['index'], ...$section['pages']]);
                $holdsThePage = $section['index'] === $page || in_array($page, $section['pages'], true);
                $listId = 'portal-section-'.$section['index']->section;
              @endphp
              <div
                x-data="{ expanded: {{ Js::from($holdsThePage) }} }"
                x-show="{{ Js::from($titles) }}.some((title) => matches(title))"
              >
                <button
                  type="button"
                  @click="expanded = ! expanded"
                  :aria-expanded="expanded || query.trim() !== '' ? 'true' : 'false'"
                  aria-controls="{{ $listId }}"
                  class="flex min-h-8 w-full cursor-pointer items-center justify-between gap-2 rounded-md px-3 py-1.5 text-left text-[13.5px] font-semibold text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
                >
                  <span>{{ $section['index']->title }}</span>
                  <span
                    class="flex text-muted transition-transform duration-(--duration-slow) ease-(--ease-out)"
                    :class="expanded || query.trim() !== '' ? 'rotate-0' : '-rotate-90'"
                  >
                    <x-icon name="chevron-down" :size="14" />
                  </span>
                </button>

                <div
                  id="{{ $listId }}"
                  class="grid transition-[grid-template-rows,visibility] duration-(--duration-slow) ease-(--ease-out)"
                  :class="expanded || query.trim() !== '' ? 'visible grid-rows-[1fr]' : 'invisible grid-rows-[0fr]'"
                >
                  <div class="min-h-0 overflow-hidden">
                    <ul role="list" class="mt-1 ml-3 flex flex-col gap-px border-l border-hairline-strong pl-2">
                      @foreach ([$section['index'], ...$section['pages']] as $item)
                        <li x-show="matches({{ Js::from(mb_strtolower($item->title)) }})">
                          <a
                            href="{{ $item->url() }}"
                            @if ($item === $page) aria-current="page" @endif
                            class="{{ $entry }} {{ $item === $page ? $entryCurrent : $entryIdle }}"
                          >{{ $item === $section['index'] ? 'Introduction' : $item->title }}</a>
                        </li>
                      @endforeach
                    </ul>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </nav>

        <div class="flex min-w-0 flex-1 flex-col">
          <div class="flex min-h-14 items-center gap-3 border-b border-hairline px-4 py-3 sm:px-10 print:hidden">
            <button
              type="button"
              @click="open = ! open"
              :aria-expanded="open ? 'true' : 'false'"
              aria-controls="portal-pages"
              class="flex h-8 shrink-0 cursor-pointer items-center gap-1.5 rounded-md border border-hairline px-2.5 text-sm font-medium text-ink hover:bg-hover focus-ring lg:hidden"
            >
              <x-icon name="menu" :size="15" />
              Pages
            </button>

            <x-breadcrumb :items="$viewModel->breadcrumb()" />
          </div>

          <div class="flex justify-center gap-14 px-4 pt-10 pb-24 sm:px-10 lg:pt-12">
            <main id="main-content" tabindex="-1" class="flex min-w-0 max-w-190 flex-1 flex-col focus:outline-none">
              <div class="flex flex-col gap-2.5">
                <h1 class="text-[34px] leading-[1.15] font-semibold tracking-[-0.03em] text-pretty text-ink">{{ $page->title }}</h1>

                @if ($viewModel->lead() !== null)
                  <p class="text-[18px] leading-normal text-pretty text-ink-soft {{ $linkStyle }}">{!! $viewModel->lead() !!}</p>
                @endif
              </div>

              <x-docs.page-actions
                class="mt-5"
                :markdown="$viewModel->markdown()"
                :markdown-url="$page->markdownUrl()"
                :assistants="$viewModel->assistantLinks()"
              />

              <div class="mt-8 flex flex-col gap-6">
                @foreach ($viewModel->blocks() as $block)
                  @if ($block['type'] === 'prose')
                    <div class="{{ $blockProse }}">{!! $block['html'] !!}</div>
                  @elseif ($block['type'] === 'note')
                    <x-docs.notice :title="$block['title'] ?? 'Note'" :class="$inlineCode.' '.$linkStyle">{!! $block['html'] !!}</x-docs.notice>
                  @elseif ($block['type'] === 'warning')
                    <x-notice tone="warning" :title="$block['title'] ?? 'Warning'" :class="$inlineCode.' '.$linkStyle">{!! $block['html'] !!}</x-notice>
                  @elseif ($block['type'] === 'screenshot')
                    @if ($block['screenshot']['src'] !== null)
                      <img src="{{ $block['screenshot']['src'] }}" alt="{{ $block['screenshot']['label'] }}" loading="lazy" class="{{ $image }}">
                    @else
                      <div class="{{ $placeholder }}">Screenshot: {{ $block['screenshot']['label'] }}</div>
                    @endif
                  @elseif ($block['type'] === 'steps')
                    <x-docs.steps>
                      @foreach ($block['steps'] as $step)
                        <x-docs.step>
                          @if ($step['title'] !== null)
                            <p class="text-[15px] leading-normal font-semibold text-ink">{{ $step['title'] }}</p>
                          @endif

                          <div class="{{ $blockProse }}">{!! $step['html'] !!}</div>

                          @if ($step['screenshot']['src'] ?? null)
                            <img src="{{ $step['screenshot']['src'] }}" alt="{{ $step['screenshot']['label'] }}" loading="lazy" class="mt-2 {{ $image }}">
                          @elseif ($step['screenshot'] !== null)
                            <div class="mt-2 {{ $placeholder }}">Screenshot: {{ $step['screenshot']['label'] }}</div>
                          @endif
                        </x-docs.step>
                      @endforeach
                    </x-docs.steps>
                  @endif
                @endforeach
              </div>

              @if ($previous !== null || $next !== null)
                <nav aria-label="Previous and next page" class="mt-18 grid gap-4 sm:grid-cols-2 print:hidden">
                  @if ($previous !== null)
                    <a href="{{ $previous->url() }}" rel="prev" class="block rounded-lg border border-hairline p-4 text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring">
                      <span class="block text-[12.5px] text-muted">Previous</span>
                      <span class="mt-1 block font-semibold">← {{ $previous->title }}</span>
                    </a>
                  @endif

                  @if ($next !== null)
                    <a href="{{ $next->url() }}" rel="next" class="block rounded-lg border border-hairline p-4 text-right text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring sm:col-start-2">
                      <span class="block text-[12.5px] text-muted">Next</span>
                      <span class="mt-1 block font-semibold">{{ $next->title }} →</span>
                    </a>
                  @endif
                </nav>
              @endif

              <footer class="mt-10 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-hairline pt-4 text-[12.5px] text-muted">
                @if ($updated = $viewModel->lastUpdated())
                  <span class="inline-flex items-center gap-2">
                    <x-icon name="clock" :size="15" />
                    <span>Last updated: <time datetime="{{ $updated->toDateString() }}" class="font-medium text-ink">{{ $viewModel->lastUpdatedLabel() }}</time></span>
                  </span>

                  <span aria-hidden="true" class="hidden text-muted-soft sm:inline">·</span>
                @endif

                <span class="inline-flex items-center gap-2">
                  <x-icon name="activity" :size="15" />
                  <span>Server generation: <span class="rounded-xs border border-hairline bg-card px-1.5 py-px font-mono font-medium text-ink">{!! \App\Http\Middleware\InsertServerGenerationTime::MARKER !!}</span></span>
                </span>
              </footer>
            </main>

            <aside class="sticky top-8 hidden w-60 flex-none flex-col gap-2.5 self-start xl:flex print:hidden">
              <x-docs.page-meta
                class="mb-4"
                :edit-url="$viewModel->editUrl()"
                :issue-url="$viewModel->issueUrl()"
              />

              @if ($viewModel->contents() !== [])
                <x-caption id="on-this-page" tone="muted">On this page</x-caption>

                <nav aria-labelledby="on-this-page" class="flex flex-col gap-0.5 border-l border-hairline">
                  @foreach ($viewModel->contents() as $heading)
                    <a
                      href="#{{ $heading['id'] }}"
                      :aria-current="current === '{{ $heading['id'] }}' ? 'location' : null"
                      class="-ml-px border-l border-transparent py-1.25 pl-3 text-sm leading-[1.4] text-muted transition-colors duration-(--duration-fast) ease-out hover:text-ink focus-ring aria-[current]:border-ink aria-[current]:font-semibold aria-[current]:text-ink"
                    >{{ $heading['label'] }}</a>
                  @endforeach
                </nav>
              @endif
            </aside>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
