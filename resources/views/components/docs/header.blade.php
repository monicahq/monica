{{-- The top bar of every documentation page: the way home, the theme, and signing in for a visitor. --}}
<header class="sticky print:hidden top-0 z-5 border-b border-hairline bg-canvas">
  <div class="mx-auto flex h-14 max-w-390 items-center gap-3.5 px-4 sm:px-7">
    <a href="{{ route('home.index') }}" class="flex shrink-0 items-center gap-2.5 text-ink rounded-md focus-ring">
      <x-logo :size="22" />
      <span class="text-lg">{{ config('app.name') }}</span>
    </a>

    <div class="ml-auto flex shrink-0 items-center gap-2.5">
      <x-theme-toggle />

      @guest
        <a
          href="{{ route('auth.login.new') }}"
          class="rounded-md px-3 py-1.75 text-[14px] text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
        >{{ __('Sign in') }}</a>
      @endguest
    </div>
  </div>
</header>
