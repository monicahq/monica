{{--
  The shell of the catalogue.

  It is the third shell rather than a branch inside one of the other two, because
  it is the only one that has to draw for somebody who is not signed in. The vault
  shell and the account shell both reach for `auth()->user()` without asking, which
  is right for them: there is no way to be on those screens without an account.

  The bar says where you are rather than who you are. The mark and the product name
  lead back out to the app, then a hairline, then the word Marketplace: the same
  shape the account shell uses, with the catalogue's name where the breadcrumb
  would be.

  On the right sits the one thing the visitor can do that the screen itself cannot.
  Signed in that is going back to the app, since the catalogue is somewhere you
  visit rather than somewhere you live. Signed out it is signing in or opening an
  account, because a catalogue you cannot install anything out of is a shop window.

  There is no `<x-bar-links>` here. Those two links are how you reach the catalogue;
  drawing them on the catalogue would be a link to the page you are on.

  @var string|null $title
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    @include('partials.meta', ['title' => $title ?? null])

    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  <body class="bg-page font-sans text-body antialiased">
    <header class="sticky top-0 z-5 flex h-15 items-center gap-3 border-b border-hairline-strong bg-canvas px-4 shadow-bar sm:px-6">
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
            class="flex h-8.5 items-center rounded-md px-2.75 text-sm text-body no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-sand hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
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
