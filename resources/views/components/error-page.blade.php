{{-- The page a failed request answers with: what went wrong, the way on, and the name of the instance. --}}
{{--
  @var string $title
  @var string $heading
  @var string|null $line
  @var \Illuminate\View\ComponentSlot|null $illustration
--}}
@props([
  'title',
  'heading',
  'line' => null,
])

<x-guest-layout :title="$title">
  <div class="min-h-screen dark:bg-[#142433]">
    <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-between gap-10 px-4 py-6 sm:px-8 sm:py-8">
      <header>
        <a href="{{ route('home.index') }}" class="focus-ring inline-flex items-center gap-2 rounded-md text-xl font-semibold tracking-tight text-ink">
          <x-logo :size="30" />
          {{ config('app.name') }}
        </a>
      </header>

      <main class="flex flex-col items-center space-y-8 text-center">
        @isset($illustration)
          {{ $illustration }}
        @endisset

        <div class="space-y-2">
          <h1 class="text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ $heading }}</h1>

          @if ($line)
            <p class="text-body">{{ $line }}</p>
          @endif
        </div>

        {{ $slot }}
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
