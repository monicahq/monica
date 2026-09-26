{{-- The way into the documentation, at /docs, open to anybody. --}}
{{-- @var \App\ViewModels\Docs\DocsViewModel $viewModel --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => __('Documentation')])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div class="flex min-h-screen flex-col">
      <x-docs.header />

      <main id="main-content" tabindex="-1" class="mx-auto flex w-full max-w-300 flex-1 flex-col gap-12 px-4 pt-12 pb-24 focus:outline-none sm:px-6 sm:pt-18">
        <div class="grid items-center gap-12 pb-6 lg:grid-cols-2">
          <div class="flex flex-col gap-4">
            <h1 class="text-[36px] leading-[1.1] font-semibold tracking-[-0.03em] text-pretty text-ink sm:text-[44px]">{{ __('Documentation') }}</h1>

            <p class="max-w-130 text-[18px] leading-normal text-pretty text-muted sm:text-[20px]">{{ __('Learn how Monica works, and connect it to your scripts, your assistant and your devices.') }}</p>

            <div class="mt-2 flex flex-wrap items-center gap-2">
              <x-button :href="route('auth.register.new')">
                {{ __('Get started with Monica') }}
                <x-icon name="chevron-right" :size="16" />
              </x-button>

              <x-button :href="route('settings.security.index')" variant="ghost">{{ __('Get an API key') }}</x-button>
            </div>
          </div>

          <div aria-hidden="true" class="relative h-65 w-full max-w-115 justify-self-end">
            <div class="absolute top-24 right-0 w-[74%] overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
              <div class="flex flex-col gap-2.5 px-5 pt-11 pb-4">
                <x-caption tone="muted">{{ __('Your addresses') }}</x-caption>

                <div class="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-x-3 gap-y-2">
                  <span class="text-base text-body">CardDAV</span>
                  <span class="truncate font-mono text-xs text-ink-soft">{{ $viewModel->host() }}/dav</span>
                  <span class="text-base text-body">MCP</span>
                  <span class="truncate font-mono text-xs text-ink-soft">{{ $viewModel->host() }}/api/mcp</span>
                </div>
              </div>
            </div>

            <div class="absolute top-0 left-0 w-[78%] overflow-hidden rounded-lg border border-hairline bg-canvas shadow-pop">
              <div class="flex flex-col gap-3 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                  <x-caption tone="muted">{{ __('API keys') }}</x-caption>

                  <span class="flex items-center gap-1.5 text-xs text-body">
                    <x-icon name="key" :size="15" />
                    {{ trans_choice(':count active|:count active', 2) }}
                  </span>
                </div>

                <div class="grid grid-cols-[110px_minmax(0,1fr)] items-center gap-x-3 gap-y-2">
                  <span class="text-base text-ink">{{ __('Personal script') }}</span>
                  <span class="truncate font-mono text-xs text-ink-soft">7Qd2kL9xVbN4…</span>
                  <span class="text-base text-ink">{{ __('Assistant') }}</span>
                  <span class="truncate font-mono text-xs text-ink-soft">Hs41pZe0RwT8…</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <ul role="list" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          @foreach ($viewModel->areas() as $area)
            <li class="flex">
              @php
                $tag = $area['url'] ? 'a' : 'div';
              @endphp

              <{{ $tag }}
                @if ($area['url']) href="{{ $area['url'] }}" @endif
                @class([
                  'flex flex-1 flex-col gap-3.5 rounded-lg border border-hairline bg-canvas p-5 text-ink shadow-panel',
                  'transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring' => $area['url'],
                ])
              >
                <div class="flex items-center justify-between">
                  <span class="flex size-12 items-center justify-center rounded-lg border border-hairline bg-card text-ink-soft">
                    <x-icon :name="$area['icon']" :size="26" />
                  </span>

                  <x-caption tone="muted">{{ $area['kind'] }}</x-caption>
                </div>

                <div class="flex flex-col gap-1.5">
                  <h2 class="flex items-center gap-1.5 text-section-title">
                    {{ $area['title'] }}

                    @if ($area['url'])
                      <x-icon name="arrow-right" :size="15" />
                    @endif
                  </h2>

                  <p class="text-base text-pretty text-body">{{ $area['description'] }}</p>
                </div>
              </{{ $tag }}>
            </li>
          @endforeach
        </ul>

        <div class="flex flex-col gap-1.5 border-t border-hairline pt-8">
          <h2 class="text-card-title text-ink">{{ __('Reading this with an assistant?') }}</h2>

          @php
            $file = '<a href="'.e(route('llmsText.index')).'" class="rounded-xs font-mono text-xs text-ink underline decoration-hairline-strong underline-offset-3 focus-ring">/llms.txt</a>';
            $specification = '<a href="'.e(route('docs.api.openapi')).'" class="rounded-xs text-ink underline decoration-hairline-strong underline-offset-3 focus-ring">'.e(__('OpenAPI spec')).'</a>';
          @endphp

          <p class="max-w-150 text-base text-pretty text-body">{!! __('Every page can be copied as Markdown, and :file lists them all in one place. The API also ships an :specification.', ['file' => $file, 'specification' => $specification]) !!}</p>
        </div>
      </main>

      <footer class="border-t border-hairline bg-card">
        <div class="mx-auto flex max-w-300 flex-wrap justify-between gap-3 px-4 py-5 text-xs text-muted sm:px-6">
          <span>{{ __('Monica is open source and can be self hosted.') }}</span>

          <a href="https://github.com/monicahq/monica" class="rounded-xs text-muted transition-colors duration-(--duration-fast) ease-out hover:text-ink focus-ring">{{ __('Source code') }}</a>
        </div>
      </footer>
    </div>
  </body>
</html>
