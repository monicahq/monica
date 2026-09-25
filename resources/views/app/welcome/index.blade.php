{{-- The letter a new account reads once, before anything else. --}}
{{-- @var \App\ViewModels\Welcome\WelcomeViewModel $viewModel --}}
<x-guest-layout :title="$viewModel->greeting()">
  <main class="flex min-h-screen justify-center px-4 pt-16 pb-12 sm:px-6 sm:pt-22 sm:pb-16">
    <div class="flex w-full max-w-160 flex-col gap-8">
      <div class="flex flex-col items-center gap-3 text-center">
        <x-logo :size="32" />

        <x-caption tone="muted" class="mt-2">{{ $viewModel->caption() }}</x-caption>

        <h1 class="text-page-title text-pretty text-ink">{{ $viewModel->greeting() }}</h1>

        <p class="max-w-110 text-[15px] leading-relaxed text-pretty text-muted">{{ $viewModel->introduction() }}</p>
      </div>

      <x-panel flush>
        <div class="flex flex-col gap-4 p-6 sm:p-8">
          @foreach ($viewModel->letter() as $paragraph)
            <p class="text-[15px] leading-[1.7] text-pretty text-body">{{ $paragraph }}</p>
          @endforeach

          <div class="mt-4 flex items-center gap-3.5 border-t border-hairline pt-5">
            <div class="flex shrink-0 -space-x-2.5">
              @foreach ($viewModel->portraitUrls() as $portraitUrl)
                <x-image
                  :src="$portraitUrl"
                  :width="40"
                  :height="40"
                  loading="eager"
                  class="size-10 rounded-full bg-sunken object-cover ring-2 ring-canvas"
                />
              @endforeach
            </div>

            <div class="space-y-0.5">
              <p class="text-card-title text-ink">{{ $viewModel->signatories() }}</p>

              <p class="text-sm text-muted">{{ $viewModel->signatoriesRole() }}</p>
            </div>
          </div>
        </div>
      </x-panel>

      <x-form method="put" :action="$viewModel->dismissUrl()" class="flex justify-center">
        <x-button>
          {{ __('Let me in') }}

          <x-icon name="arrow-right" :size="16" />
        </x-button>
      </x-form>
    </div>
  </main>
</x-guest-layout>
