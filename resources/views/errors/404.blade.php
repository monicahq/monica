{{-- What somebody sees when the address they asked for leads nowhere. --}}
<x-guest-layout :title="__('Page not found')">
  <div class="min-h-screen dark:bg-[#142433]">
    <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-between gap-10 px-4 py-6 sm:px-8 sm:py-8">
      <header>
        <a href="{{ route('home.index') }}" class="focus-ring inline-flex items-center gap-2 rounded-md text-xl font-semibold tracking-tight text-ink">
          <x-logo :size="30" />
          {{ config('app.name') }}
        </a>
      </header>

      <main class="flex flex-col items-center space-y-8 text-center">
        <img
          src="{{ asset('images/not-found.webp') }}"
          alt=""
          width="1335"
          height="532"
          loading="lazy"
          decoding="async"
          class="h-auto w-full max-w-5xl dark:hidden"
        />

        <img
          src="{{ asset('images/not-found-dark.webp') }}"
          alt=""
          width="1986"
          height="792"
          loading="lazy"
          decoding="async"
          class="hidden h-auto w-full max-w-5xl dark:block"
        />

        <div class="space-y-2">
          <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">
            {{ __('We’re sorry, but the page you’re looking for doesn’t exist.') }}
          </h1>

          <p class="text-body">{{ __('It may have moved, or the link might be broken.') }}</p>
        </div>

        <x-button :href="route('home.index')">{{ __('Return home') }}</x-button>
      </main>

      <footer class="flex flex-col items-center gap-3 text-sm text-muted sm:flex-row sm:justify-between">
        <p>{{ config('app.name') }} © {{ now()->format('Y') }} · {{ __('Open source personal CRM') }}</p>

        <nav aria-label="{{ __('Footer') }}" class="flex gap-6">
          <a href="{{ route('docs.index') }}" class="focus-ring rounded-sm hover:text-ink">{{ __('Docs') }}</a>
          <a href="{{ config('monica.terms_url') }}" class="focus-ring rounded-sm hover:text-ink">{{ __('Legal') }}</a>
        </nav>
      </footer>
    </div>
  </div>
</x-guest-layout>
