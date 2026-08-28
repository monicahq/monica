{{--
  One endpoint of the API reference, at /docs/api/{section}, open to anybody.

  Each endpoint is a page of its own rather than an anchor into a long one, so a
  link to it opens it, the browser's back button walks between endpoints, and
  every page is one document with one heading.

  The chrome around it is deliberately thin. There is no marketing site to hang
  this off yet, so it signs itself, offers the theme switch, and gets out of the
  way.

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
        <div class="mx-auto flex h-13 max-w-[97.5rem] items-center gap-3.5 px-4 sm:px-7">
          <a href="{{ route('home.index') }}" class="flex shrink-0 items-center gap-2.5 text-ink rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring">
            <x-logo :height="22" />
            <span class="text-[16px] font-semibold tracking-[-0.02em]">{{ config('app.name') }}</span>
          </a>

          <div class="ml-auto flex shrink-0 items-center gap-2.5">
            <x-theme-toggle />

            <a
              href="{{ route('auth.login.new') }}"
              class="rounded-md px-3 py-1.75 text-[14px] text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >{{ __('Sign in') }}</a>
          </div>
        </div>
      </header>

      <x-api-docs.subheader />

      <div class="mx-auto flex max-w-[97.5rem] items-start">
        <x-api-docs.sidebar :navigation="$navigation" :current="$section->id" />

        <main id="main-content" tabindex="-1" class="min-h-[calc(100vh-6.75rem)] min-w-0 flex-1 bg-canvas px-4 py-8 focus:outline-none sm:px-7 sm:pb-24">
          <x-api-docs.endpoint :section="$section" />
        </main>
      </div>
    </div>
  </body>
</html>
