{{-- What somebody sees when the address they were sent does not open anything. --}}
{{-- @var string|null $line --}}
<x-guest-layout :title="__('This link no longer works')">
  <main class="flex min-h-screen flex-col items-center justify-center gap-6 px-6 py-10">
    <x-logo :size="34" />

    <div class="w-full max-w-md rounded-lg border border-hairline bg-canvas p-6 ">
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
