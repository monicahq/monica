{{-- The shell of the screens that stand outside any one vault. --}}
{{--
  @var string|null $title
  @var \App\ViewModels\FullAccessOfferViewModel|null $fullAccessOffer
  @var \Illuminate\View\ComponentSlot|null $breadcrumb
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])
    <title>{{ \Laravel\Head\Facades\Head::toArray()['title'] }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <header class="sticky print:hidden top-0 z-5 flex h-14 items-center gap-2.5 border-b border-topbar-hairline bg-topbar/85 px-4 backdrop-blur-sm sm:px-6">
<x-product-link :size="28" />

      <div class="ml-auto">
        <x-bar-links :settings="false" />
      </div>
    </header>

    @if ($fullAccessOffer !== null)
      <x-full-access-offer :viewModel="$fullAccessOffer" />
    @endif

    @isset($breadcrumb)
      <div class="border-b border-hairline bg-canvas">
        <div class="mx-auto max-w-240 px-4 py-2.5 sm:px-6">
          {{ $breadcrumb }}
        </div>
      </div>
    @endisset

    <main class="mx-auto max-w-240 space-y-8 px-4 pb-22 sm:px-6 {{ isset($breadcrumb) ? 'pt-7.5' : 'pt-14' }}">
      {{ $slot }}
    </main>

    <x-toaster />
  </body>
</html>
