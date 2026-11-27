{{-- One change of the API changelog, at /docs/api/changelog/{entry}, open to anybody. --}}
{{--
  @var array $navigation
  @var \App\ViewModels\Docs\ApiChangelogEntry $entry
  @var list<\App\ViewModels\Docs\ApiChangelogEntry> $sameRelease
  @var \App\ViewModels\Docs\ApiDocsViewModel $reference
  @var string $markdown
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $entry->title.' · '.__('API reference'), 'description' => $entry->summary])
    <title>{{ \Laravel\Head\Facades\Head::toArray()['title'] }}</title>

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
            ['label' => 'Changelog', 'url' => route('docs.api.changelog.index')],
            ['label' => $entry->date->format('j F Y')],
          ]" />
        </div>
      </div>

      <x-api-docs.subheader />

      <div class="mx-auto flex max-w-390 items-start">
        <x-api-docs.sidebar :navigation="$navigation" current="changelog" />

        <main id="main-content" tabindex="-1" class="min-h-[calc(100vh-7rem)] min-w-0 flex-1 bg-canvas px-4 py-8 focus:outline-none sm:px-7 sm:pb-24">
          <div class="flex flex-wrap items-start gap-10">
            <article class="max-w-180 min-w-0 flex-1 basis-130">
              <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('docs.api.changelog.index') }}" data-turbo="true" class="flex items-center gap-1.5 rounded-xs text-sm text-muted hover:text-ink focus-ring">
                  <x-icon name="arrow-right" :size="14" class="rotate-180" />
                  All changes
                </a>

                <x-api-docs.markdown-actions :markdown="$markdown" :url="route('docs.api.changelog.markdown.show', ['entry' => $entry->id])" />
              </div>

              <p class="mt-6 flex flex-wrap items-center gap-2 text-xs text-muted">
                <time datetime="{{ $entry->date->toDateString() }}">{{ $entry->date->format('j F Y') }}</time>
                <span aria-hidden="true" class="text-muted-soft">·</span>
                <span class="font-medium">{{ $entry->kind }}</span>
                <span aria-hidden="true" class="text-muted-soft">·</span>
                <span>{{ $entry->group }}</span>
                @if ($entry->breaking)
                  <x-badge tone="danger">Breaking</x-badge>
                @endif
              </p>

              <h1 class="mt-3 mb-3 text-page-title text-pretty text-ink">{{ $entry->title }}</h1>
              <p class="text-[14.5px] leading-[1.65] text-pretty">{{ $entry->summary }}</p>

              @if ($entry->breaking)
                <div role="note" class="mt-6 flex gap-3 rounded-lg border border-warning-edge bg-warning-tint px-4 py-3.5">
                  <x-icon name="info" :size="18" class="mt-px shrink-0 text-warning" />
                  <div>
                    <p class="font-semibold text-ink">Why this is a breaking change</p>
                    <p class="mt-1 leading-[1.6] text-pretty text-ink-soft">{{ $entry->impact }}</p>
                  </div>
                </div>
              @endif

              <h2 class="mt-10 mb-2 text-section-title text-ink">Why it changed</h2>
              <p class="leading-[1.65] text-pretty">{{ $entry->why }}</p>

              <h2 class="mt-10 mb-3 text-section-title text-ink">Changes</h2>
              <div tabindex="0" role="region" aria-label="Changes" class="overflow-x-auto rounded-lg border border-hairline bg-canvas shadow-panel focus-ring">
                <table class="w-full min-w-120 text-left">
                  <thead class="border-b border-hairline bg-card text-xs text-ink">
                    <tr>
                      <th scope="col" class="px-4 py-2.5 font-semibold">Field</th>
                      <th scope="col" class="px-4 py-2.5 font-semibold">Change</th>
                      <th scope="col" class="px-4 py-2.5 font-semibold">Endpoints</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($entry->changes as $position => $change)
                      <tr class="border-t border-hairline align-top first:border-t-0">
                        <td class="px-4 py-3"><code class="rounded-sm border border-hairline bg-card px-1.5 py-0.5 font-mono text-xs text-ink">{{ $change['field'] }}</code></td>
                        <td class="px-4 py-3 text-sm text-ink-soft">{{ $change['change'] }}</td>
                        <td class="px-4 py-3">
                          @forelse ($entry->endpointsOf($position) as $endpoint)
                            @php
                              $section = $reference->sectionFor($endpoint['method'], $endpoint['path']);
                            @endphp
                            <div class="flex min-w-0 items-center gap-2 py-1">
                              <x-api-docs.method-badge :method="$endpoint['method']" class="w-12 shrink-0" />
                              @if ($section)
                                <a href="{{ route('docs.api.show', ['section' => $section->id]) }}" data-turbo="true" class="font-mono text-xs break-all text-ink underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring">{{ $endpoint['path'] }}</a>
                              @else
                                <span class="font-mono text-xs break-all text-ink">{{ $endpoint['path'] }}</span>
                              @endif
                            </div>
                          @empty
                            <span class="text-sm text-ink-soft">All endpoints</span>
                          @endforelse
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>

              @if ($sameRelease !== [])
                <h2 class="mt-10 mb-3 text-section-title text-ink">In the same release</h2>
                <ul class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
                  @foreach ($sameRelease as $sibling)
                    <x-api-docs.changelog-row :entry="$sibling" />
                  @endforeach
                </ul>
              @endif
            </article>

            @if ($entry->example !== null)
              <aside aria-label="Example" class="flex max-w-115 min-w-0 flex-1 basis-85 flex-col gap-4 lg:sticky lg:top-4">
                <div
                  x-data="{ side: 'after', copied: false, shapes: {{ Js::from(['before' => $entry->example['before'] ?? null, 'after' => $entry->example['after']]) }} }"
                  class="overflow-hidden rounded-lg border border-hairline bg-sunken"
                >
                  <div class="flex items-center gap-2 border-b border-hairline-soft bg-hover px-3 py-2">
                    <span class="flex-1 font-mono text-xs font-medium text-ink">{{ $entry->example['label'] ?? 'Example' }}</span>

                    @if ($entry->hasBefore())
                      <div role="group" aria-label="Shape shown" class="flex gap-0.5">
                        @foreach (['before' => 'Before', 'after' => 'After'] as $side => $label)
                          <button
                            type="button"
                            @click="side = '{{ $side }}'"
                            :aria-pressed="side === '{{ $side }}' ? 'true' : 'false'"
                            :class="side === '{{ $side }}' ? 'bg-primary text-on-primary font-medium' : 'text-body hover:text-ink'"
                            class="cursor-pointer rounded-xs px-2.5 py-1 text-xs whitespace-nowrap transition-colors duration-(--duration-fast) ease-out focus-ring"
                          >{{ $label }}</button>
                        @endforeach
                      </div>
                    @endif

                    <button
                      type="button"
                      @click="window.copyText(shapes[side]).then((ok) => { copied = ok; setTimeout(() => (copied = false), 1600) })"
                      class="cursor-pointer rounded-xs px-2 py-1 text-xs text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
                      aria-live="polite"
                      x-text="copied ? 'Copied' : 'Copy'"
                    >Copy</button>
                  </div>

                  <pre tabindex="0" role="region" aria-label="Example shape" class="overflow-x-auto px-4 py-3.5 focus-ring-inset font-mono text-xs leading-[1.75] text-ink-soft" x-text="shapes[side]">{{ $entry->example['after'] }}</pre>
                </div>

                @if ($entry->hasBefore())
                  <p class="text-xs leading-[1.55] text-pretty text-muted">The same request or answer, before and after the change. Switch between them to compare.</p>
                @endif
              </aside>
            @endif
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
