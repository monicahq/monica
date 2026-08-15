{{--
  What somebody sees when the address they were sent does not open anything.

  It says the same thing whether the link never existed, ran out on its own, or
  was revoked, because the action behind it refuses all three identically: telling
  them apart would let somebody trying addresses work out which ones are real.

  There is nothing to do here and nothing is offered. No sign in, no sign up, no
  way to ask for another link. Whoever sent the address is the only person who can
  make a new one, and the sentence says so.

  @var string|null $line
--}}
<x-guest-layout :title="__('This link no longer works')">
  <main class="flex min-h-screen flex-col items-center justify-center gap-6 px-6 py-10">
    <x-logo :size="34" />

    <div class="w-full max-w-md rounded-lg border border-hairline bg-canvas p-6 shadow-raised">
      <div class="flex flex-col gap-3">
        <span class="flex size-9 items-center justify-center rounded-sm bg-sunken text-muted" aria-hidden="true">
          <x-icon name="lock" :size="19" :stroke-width="1.6" />
        </span>

        <h1 class="text-section-title text-ink">{{ __('This link no longer works') }}</h1>

        <p class="text-sm text-pretty text-body">
          {{ $line ?? __('It may have run out, or the person who shared it may have ended it. Nothing is wrong with your browser, and there is nothing to do here. Ask them for a new link if you still need one.') }}
        </p>
      </div>
    </div>

    <p class="text-xs text-muted-soft">{{ config('app.name') }}</p>
  </main>
</x-guest-layout>
