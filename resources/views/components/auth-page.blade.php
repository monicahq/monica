{{-- The shell of every screen a visitor signs in through: one centred column over a landscape. --}}
{{--
  @var string $title
  @var string|null $description
--}}
@props([
  'title',
  'description' => null,
])

<x-guest-layout :title="$title">
  <div class="relative isolate flex min-h-screen flex-col">
    <img
      src="{{ asset('images/sign-in-landscape.webp') }}"
      alt=""
      width="1983"
      height="793"
      decoding="async"
      loading="lazy"
      aria-hidden="true"
      class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 w-full select-none dark:hidden"
    />

    <img
      src="{{ asset('images/sign-in-landscape-dark.webp') }}"
      alt=""
      width="1983"
      height="793"
      decoding="async"
      loading="lazy"
      aria-hidden="true"
      class="pointer-events-none absolute inset-x-0 bottom-0 -z-10 hidden w-full select-none dark:block"
    />

    <main class="flex flex-1 flex-col justify-center px-6 py-10">
      <div class="mx-auto w-full max-w-md space-y-6">
        <div class="space-y-3 text-center">
          <a href="{{ route('home.index') }}" class="focus-ring inline-flex items-center gap-3 rounded-md no-underline">
            <x-logo :size="36" />

            <span class="text-3xl font-medium tracking-tight text-ink">{{ config('app.name') }}</span>
          </a>

          <h1 class="text-3xl font-semibold tracking-tight text-ink">{{ $title }}</h1>

          @isset($description)
            <p class="text-sm text-body">{{ $description }}</p>
          @endisset
        </div>

        {{ $slot }}
      </div>
    </main>

    <footer class="flex flex-wrap items-center justify-between gap-4 px-6 pb-6 sm:px-20">
      <div class="flex items-center gap-4 text-xs text-body">
        <p>© {{ config('app.name') }} {{ now()->format('Y') }}</p>

        <a href="{{ route('docs.index') }}" class="focus-ring rounded-sm hover:text-ink">{{ __('Docs') }}</a>
      </div>

      <div class="flex items-center gap-2">
        {{ $footer ?? '' }}

        <x-theme-toggle class="border border-hairline-strong bg-canvas" />
      </div>
    </footer>
  </div>
</x-guest-layout>
