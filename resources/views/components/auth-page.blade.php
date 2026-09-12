{{-- The shell of every screen a visitor signs in through: the card on one side, a line from The Office on the other. --}}
{{--
  @var string $title
  @var string|null $description
  @var array{text: string, author: string, source: string}|null $quote
--}}
@props([
  'title',
  'description' => null,
  'quote' => null,
])

<x-guest-layout :title="$title">
  <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.92fr)]">
    <main class="flex min-w-0 flex-col justify-center px-6 py-10 sm:px-15">
      <div class="mx-auto my-auto w-full max-w-md space-y-6">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <x-logo :size="30" />

            <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $title }}</h1>

            <x-theme-toggle class="ml-auto" />
          </div>

          @isset($description)
            <p class="text-sm text-body">{{ $description }}</p>
          @endisset
        </div>

        {{ $slot }}
      </div>

      <p class="pt-10 text-center text-xs text-muted-soft">
        © {{ config('app.name') }} {{ now()->format('Y') }} ⋅ {{ __('Open source HR') }}
      </p>
    </main>

    @isset($quote)
      @include('app.auth._quote', ['quote' => $quote])
    @endisset
  </div>
</x-guest-layout>
