{{-- One endpoint of the API reference, at /docs/api/{section}, open to anybody. --}}
{{--
  @var array $navigation
  @var \App\ViewModels\Docs\ApiDocsSection $section
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $section->title.' · '.__('API reference'), 'description' => $section->description])

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
            ['label' => $section->title],
          ]" />
        </div>
      </div>

      <x-api-docs.subheader />

      <div class="mx-auto flex max-w-390 items-start">
        <x-api-docs.sidebar :navigation="$navigation" :current="$section->id" />

        <main id="main-content" tabindex="-1" class="min-h-[calc(100vh-7rem)] min-w-0 flex-1 bg-canvas px-4 py-8 focus:outline-none sm:px-7 sm:pb-24">
          <x-api-docs.endpoint :section="$section" />
        </main>
      </div>
    </div>
  </body>
</html>
