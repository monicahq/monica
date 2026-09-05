{{-- The shell of the catalogue. --}}
{{-- @var string|null $title --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <header class="sticky top-0 z-5 flex h-14 items-center gap-3 border-b border-hairline bg-canvas/85 px-4 backdrop-blur-[8px] sm:px-6">
      <a href="{{ route('home.index') }}" class="flex items-center gap-2.25 no-underline">
        <x-logo :size="26" />

        <span class="truncate text-sm font-semibold tracking-tight text-ink">{{ config('app.name') }}</span>
      </a>

      <span aria-hidden="true" class="h-5.5 w-px bg-hairline-strong"></span>

      <span class="text-sm font-semibold text-ink">{{ __('Marketplace') }}</span>

      <div class="ml-auto flex items-center gap-2.5">
        <x-theme-toggle />

        @auth
          <x-button.bar :href="route('home.index')" data-turbo="true" size="small">
            <x-slot:icon><x-icon name="vault" :size="16" :stroke-width="1.5" /></x-slot:icon>

            <span class="max-sm:hidden">{{ __('Back to the app') }}</span>
          </x-button.bar>

          <x-avatar :user="auth()->user()" :size="32" />
        @else
          <a
            href="{{ route('auth.login.new') }}"
            class="flex h-8.5 items-center rounded-md px-2.75 text-sm text-body no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
          >
            {{ __('Sign in') }}
          </a>

          <x-button :href="route('auth.register.new')" size="small">{{ __('Create an account') }}</x-button>
        @endauth
      </div>
    </header>

    <main class="mx-auto flex w-full max-w-280 flex-col gap-8 px-4 pt-11 pb-16 sm:px-6">
      {{ $slot }}
    </main>

    <x-toaster />
  </body>
</html>
