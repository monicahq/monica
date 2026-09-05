{{-- One endpoint of the API reference, at /docs/api/{section}, open to anybody. --}}
{{--
  @var array $navigation
  @var \App\ViewModels\Docs\ApiDocsSection $section
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $section->title.' · '.__('API reference')])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <div x-data="{ lang: 'curl', query: '' }">
      <header class="sticky top-0 z-5 border-b border-hairline bg-canvas">
        <div class="mx-auto flex h-14 max-w-[97.5rem] items-center gap-3.5 px-4 sm:px-7">
          <a href="{{ route('home.index') }}" class="flex shrink-0 items-center gap-2.5 text-ink rounded-md focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none">
            <x-logo :height="22" />
            <span class="text-[16px] font-semibold tracking-[-0.02em]">{{ config('app.name') }}</span>
          </a>

          <div class="ml-auto flex shrink-0 items-center gap-2.5">
            <x-theme-toggle />

            <a
              href="{{ route('auth.login.new') }}"
              class="rounded-md px-3 py-1.75 text-[14px] text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
            >{{ __('Sign in') }}</a>
          </div>
        </div>
      </header>

      <x-api-docs.subheader />

      <div class="mx-auto flex max-w-[97.5rem] items-start">
        <x-api-docs.sidebar :navigation="$navigation" :current="$section->id" />

        <main id="main-content" tabindex="-1" class="min-h-[calc(100vh-7rem)] min-w-0 flex-1 bg-canvas px-4 py-8 focus:outline-none sm:px-7 sm:pb-24">
          <x-api-docs.endpoint :section="$section" />
        </main>
      </div>
    </div>
  </body>
</html>
