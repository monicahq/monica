{{-- The API changelog at /docs/api/changelog: every change, grouped by the date it shipped. --}}
{{--
  @var array $navigation
  @var list<array{date: \Carbon\CarbonImmutable, note: string|null, latest: bool, count: int, countLabel: string, groups: list<array{name: string, entries: list<\App\ViewModels\Docs\ApiChangelogEntry>}>}> $releases
  @var string $totalLabel
  @var string $markdown
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Helpers\AvailableLocales::direction(app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => 'Changelog · '.__('API reference'), 'description' => \App\ViewModels\Docs\ApiChangelogMarkdown::INTRODUCTION])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div x-data="{ lang: 'curl', query: '' }">
      <x-docs.header />

      <div class="border-b border-hairline bg-canvas print:hidden">
        <div class="mx-auto flex h-14 max-w-390 items-center gap-5 px-4 sm:px-7">
          <x-breadcrumb :items="[
            ['label' => __('Home page'), 'url' => route('home.index')],
            ['label' => __('Documentation'), 'url' => route('docs.index')],
            ['label' => 'API', 'url' => route('docs.api.index')],
            ['label' => 'Changelog'],
          ]" />
        </div>
      </div>

      <x-api-docs.subheader />

      <div class="mx-auto flex max-w-390 items-start">
        <x-api-docs.sidebar :navigation="$navigation" current="changelog" />

        <main id="main-content" tabindex="-1" class="min-h-[calc(100vh-7rem)] min-w-0 flex-1 bg-canvas px-4 py-8 focus:outline-none sm:px-7 sm:pb-24">
          <div x-data="apiChangelog" class="flex flex-wrap items-start gap-12">
            <div class="max-w-220 min-w-0 flex-1 basis-140">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-mono text-xs text-muted">v1</span>

                <x-api-docs.markdown-actions :markdown="$markdown" :url="route('docs.api.changelog.markdown.index')" />
              </div>

              <h1 class="mt-4 mb-2 text-page-title text-ink">Changelog</h1>
              <p class="max-w-160 text-[14px] leading-[1.6] text-pretty">{{ \App\ViewModels\Docs\ApiChangelogMarkdown::INTRODUCTION }}</p>

              @if ($releases === [])
                <p class="mt-8 text-muted">{{ \App\ViewModels\Docs\ApiChangelogMarkdown::EMPTY }}</p>
              @else
                <div class="mt-8 flex flex-wrap items-center gap-3 border-b border-hairline pb-4">
                  <label class="flex h-8 max-w-65 flex-1 basis-50 items-center gap-2 rounded-md border border-hairline-strong bg-card px-2.5 transition-colors duration-(--duration-fast) ease-out focus-within:ring-[3px] focus-within:ring-focus-ring/45">
                    <span class="sr-only">Search changes</span>
                    <x-icon name="search" :size="15" class="shrink-0 text-muted" />
                    <input type="search" x-model="query" x-ref="search" placeholder="Search changes" class="w-full min-w-0 border-0 bg-transparent p-0 text-sm text-ink placeholder-muted focus:outline-none" />
                  </label>

                  <div role="group" aria-label="Kind of change" class="flex flex-wrap gap-0.5 rounded-md border border-hairline bg-card p-0.5">
                    @foreach (['All', ...\App\ViewModels\Docs\ApiChangelogViewModel::KINDS] as $kind)
                      <button
                        type="button"
                        @click="kind = @js($kind)"
                        :aria-pressed="kind === @js($kind) ? 'true' : 'false'"
                        :class="kind === @js($kind) ? 'bg-primary text-on-primary' : 'text-body hover:text-ink'"
                        class="h-6.5 cursor-pointer rounded-sm px-2.5 text-sm font-medium transition-colors duration-(--duration-fast) ease-out focus-ring"
                      >{{ $kind }}</button>
                    @endforeach
                  </div>

                  <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-ink">
                    <input type="checkbox" x-model="breakingOnly" class="size-3.75 accent-primary" />
                    Breaking changes only
                  </label>

                  <span class="text-xs text-muted" aria-live="polite" x-text="countLabel">{{ $totalLabel }}</span>
                </div>

                <div x-show="count === 0" x-cloak class="flex flex-col items-center gap-2 py-12 text-center">
                  <x-icon name="search" :size="24" class="text-muted" />
                  <p class="font-semibold text-ink">No change matches</p>
                  <p class="text-muted">Try another word, or clear the filters to see every release.</p>
                  <button type="button" @click="clear()" class="mt-2 cursor-pointer rounded-md border border-hairline bg-card px-3 py-1.75 text-sm font-medium text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring">Clear filters</button>
                </div>

                @foreach ($releases as $release)
                  <section id="release-{{ $release['date']->toDateString() }}" x-show="holdsMatch($el)" class="mt-10 scroll-mt-4">
                    <div class="flex flex-wrap items-baseline gap-2.5">
                      <h2 class="text-section-title text-ink"><time datetime="{{ $release['date']->toDateString() }}">{{ $release['date']->format('j F Y') }}</time></h2>
                      @if ($release['latest'])
                        <x-badge tone="success">Latest</x-badge>
                      @endif
                    </div>

                    @if ($release['note'] !== null)
                      <p class="mt-1.5 max-w-160 text-pretty text-muted">{{ $release['note'] }}</p>
                    @endif

                    @foreach ($release['groups'] as $group)
                      <div x-show="holdsMatch($el)" class="mt-5">
                        <h3 class="mb-2 text-[13.5px] font-semibold text-ink">{{ $group['name'] }}</h3>

                        <ul class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
                          @foreach ($group['entries'] as $entry)
                            <x-api-docs.changelog-row :entry="$entry" filterable />
                          @endforeach
                        </ul>
                      </div>
                    @endforeach
                  </section>
                @endforeach
              @endif
            </div>

            @if ($releases !== [])
              <nav aria-labelledby="releases-heading" class="flex min-w-45 grow basis-50 flex-col gap-1.5 lg:sticky lg:top-4 lg:grow-0">
                <h2 id="releases-heading" class="px-2.5 text-2xs font-medium tracking-caption text-muted uppercase">Releases</h2>
                <ul class="flex flex-col gap-0.5">
                  @foreach ($releases as $release)
                    <li x-show="holdsMatch(document.getElementById('release-{{ $release['date']->toDateString() }}'))">
                      <a href="#release-{{ $release['date']->toDateString() }}" class="flex items-center justify-between rounded-md px-2.5 py-1.75 text-sm text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring">
                        <span>{{ $release['date']->format('j M Y') }}</span>
                        <span class="text-xs text-muted">{{ $release['countLabel'] }}</span>
                      </a>
                    </li>
                  @endforeach
                </ul>
              </nav>
            @endif
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
