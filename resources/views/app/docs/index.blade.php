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

      <main id="main-content" tabindex="-1" class="mx-auto flex w-full max-w-300 flex-1 flex-col px-4 focus:outline-none sm:px-6 xl:max-w-350 2xl:px-8">
        <div class="grid items-center gap-12 py-12 sm:py-16 lg:grid-cols-2 lg:gap-16 lg:py-20 2xl:py-28">
          <div class="flex max-w-160 flex-col gap-5 2xl:max-w-180">
            <h1 class="text-[40px] leading-[1.05] font-semibold tracking-[-0.04em] text-pretty text-ink sm:text-[48px] lg:text-[56px] 2xl:text-[64px]">{{ __('Documentation') }}</h1>

            <p class="text-[18px] leading-normal text-pretty text-muted sm:text-[20px] 2xl:text-[22px]">{{ __('Learn how Monica works, and connect it to your scripts, your assistant and your devices.') }}</p>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
              <x-button :href="$viewModel->registrationIsEnabled() ? route('auth.register.new') : route('auth.login.new')">
                {{ __('Get started with Monica') }}
                <x-icon name="chevron-right" :size="16" />
              </x-button>

              <x-button :href="route('settings.security.index')" variant="ghost">{{ __('Get an API key') }}</x-button>
            </div>
          </div>

          <div aria-hidden="true" class="relative w-full max-w-lg justify-self-center lg:max-w-xl lg:justify-self-end 2xl:max-w-2xl">
            <div class="relative z-2 w-full max-w-100 overflow-hidden rounded-lg border border-hairline bg-canvas px-6 py-5 shadow-pop 2xl:max-w-110">
              <div class="flex items-center justify-between gap-3">
                <span class="flex items-center gap-2">
                  <span class="size-2 rounded-full bg-[oklch(0.55_0.17_295)]"></span>
                  <x-caption tone="muted">{{ __('API keys') }}</x-caption>
                </span>

                <span class="flex items-center gap-1.5 text-base text-success">
                  <span class="size-1.75 rounded-full bg-success"></span>
                  {{ trans_choice(':count active|:count active', 2) }}
                </span>
              </div>

              <div class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] items-baseline gap-x-6 gap-y-3">
                <span class="text-base text-ink">{{ __('Personal script') }}</span>
                <span class="truncate font-mono text-base text-body">7Qd2kL9xVbN4…</span>
                <span class="text-base text-ink">{{ __('Assistant') }}</span>
                <span class="truncate font-mono text-base text-body">Hs41pZe0RwT8…</span>
              </div>
            </div>

            <div class="relative z-1 -mt-9 ml-auto w-full max-w-105 overflow-hidden rounded-lg border border-hairline bg-canvas px-6 pt-14 pb-5 shadow-panel 2xl:max-w-115">
              <span class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-[oklch(0.6_0.13_190)]"></span>
                <x-caption tone="muted">{{ __('Your addresses') }}</x-caption>
              </span>

              <div class="mt-4 grid grid-cols-[auto_minmax(0,1fr)] items-baseline gap-x-8 gap-y-3">
                @if ($viewModel->carddavIsOn())
                  <span class="text-base text-body">CardDAV</span>
                  <span class="truncate font-mono text-base text-ink">{{ $viewModel->host() }}/dav</span>
                @endif

                @if ($viewModel->mcpIsOn())
                  <span class="text-base text-body">MCP</span>
                  <span class="truncate font-mono text-base text-ink">{{ $viewModel->host() }}/mcp</span>
                @endif
              </div>
            </div>
          </div>
        </div>

        <ul role="list" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 xl:gap-6">
          @foreach ($viewModel->areas() as $area)
            <li class="flex">
              @php
                $tag = $area['url'] ? 'a' : 'div';
              @endphp

              <{{ $tag }}
                @if ($area['url']) href="{{ $area['url'] }}" @endif
                style="--accent: oklch(0.58 0.16 {{ $area['hue'] }})"
                @class([
                  'flex flex-1 flex-col gap-3 rounded-lg border border-hairline bg-canvas p-6 text-ink shadow-panel',
                  'transition-colors duration-(--duration-fast) ease-out hover:border-[color-mix(in_oklch,var(--accent)_45%,var(--hairline))] focus-ring' => $area['url'],
                ])
              >
                <div class="flex items-center justify-between">
                  <span class="flex size-14 items-center justify-center rounded-lg border border-[color-mix(in_oklch,var(--accent)_25%,var(--canvas))] bg-[color-mix(in_oklch,var(--accent)_10%,var(--canvas))] text-(--accent)">
                    <x-icon :name="$area['icon']" :size="26" />
                  </span>

                  <span class="text-xs font-medium tracking-[0.12em] text-[color-mix(in_oklch,var(--accent)_75%,var(--ink))] uppercase">{{ $area['kind'] }}</span>
                </div>

                <h2 class="mt-2 flex items-center gap-2 text-xl text-ink">
                  {{ $area['title'] }}

                  @if ($area['url'])
                    <x-icon name="arrow-right" :size="18" />
                  @endif
                </h2>

                <p class="text-base leading-relaxed text-pretty text-body">{{ $area['description'] }}</p>
              </{{ $tag }}>
            </li>
          @endforeach
        </ul>

        <div class="mt-16 flex flex-col gap-3 border-t border-hairline pt-10 pb-16 lg:mt-20">
          <h2 class="text-lg text-ink">{{ __('Reading this with an assistant?') }}</h2>

          @php
            $file = '<a href="'.e(route('llmsText.index')).'" class="rounded-xs font-mono text-ink underline decoration-[oklch(0.6_0.13_190)] underline-offset-3 focus-ring">/llms.txt</a>';
            $specification = '<a href="'.e(route('docs.api.openapi')).'" class="rounded-xs text-ink underline decoration-[oklch(0.6_0.13_190)] underline-offset-3 focus-ring">'.e(__('OpenAPI spec')).'</a>';
          @endphp

          <p class="max-w-180 text-[16px] leading-relaxed text-pretty text-body">{!! __('Every page can be copied as Markdown, and :file lists them all in one place. The API also ships an :specification.', ['file' => $file, 'specification' => $specification]) !!}</p>
        </div>
      </main>

      <footer class="border-t border-hairline bg-card">
        <div class="mx-auto flex max-w-300 flex-wrap justify-between gap-3 px-4 py-5 text-xs text-muted sm:px-6 xl:max-w-350 2xl:px-8">
          <span>{{ __('Monica is open source and can be self hosted.') }}</span>

          <a href="https://github.com/monicahq/monica" class="rounded-xs text-muted transition-colors duration-(--duration-fast) ease-out hover:text-ink focus-ring">{{ __('Source code') }}</a>
        </div>
      </footer>
    </div>
  </body>
</html>
