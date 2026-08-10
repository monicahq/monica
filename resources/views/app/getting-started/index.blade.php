{{--
  What somebody sees before they have made anything: a letter from whoever
  writes the product, and the short list of what people usually do first.

  Nothing on the list is ticked by hand. Each box says what the account already
  holds, so it is drawn rather than offered, and a step nobody can act on yet
  names what is coming instead of pointing at a page that does not exist.

  @var \App\ViewModels\GettingStarted\GettingStartedViewModel $viewModel
--}}
<x-account-layout :title="$viewModel->greeting()">
  <div class="space-y-2">
    <div class="space-y-0.5">
      <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $viewModel->greeting() }}</h1>

      <p class="text-sm text-muted">{{ $viewModel->introduction() }}</p>
    </div>

    <div class="space-y-6 rounded-xl border border-hairline bg-canvas px-9 py-8">
      <div class="max-w-160 space-y-4">
        @foreach ($viewModel->letter() as $paragraph)
          <p class="text-[15px] leading-relaxed text-pretty text-body">{{ $paragraph }}</p>
        @endforeach
      </div>

      <div class="flex flex-wrap items-center gap-3.5 border-t border-hairline-soft pt-5">
        <x-image
          :src="$viewModel->portraitUrl()"
          :alt="$viewModel->founderName()"
          :width="56"
          :height="56"
          class="size-14 shrink-0 rounded-full bg-sunken object-cover"
        />

        <div class="space-y-0.5">
          <p class="text-[15px] font-semibold text-ink">{{ $viewModel->founderName() }}</p>

          <p class="text-[13px] text-muted">{{ $viewModel->founderRole() }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 max-sm:w-full sm:ml-auto">
          @if ($viewModel->writeUrl())
            <a href="{{ $viewModel->writeUrl() }}" class="text-sm text-muted hover:text-brand hover:underline">
              {{ __('Write to :name', ['name' => 'Regis']) }}
            </a>
          @endif

          <x-button :href="$viewModel->createVaultUrl()" data-turbo="true">{{ __('Create your first vault') }}</x-button>
        </div>
      </div>
    </div>
  </div>

  <div class="space-y-2">
    <div class="flex items-baseline justify-between gap-4">
      <div class="space-y-0.5">
        <h2 class="text-lg font-semibold text-ink">{{ __('Getting set up') }}</h2>

        <p class="text-sm text-muted">{{ __('None of this is required. It is the short version of what people usually do first.') }}</p>
      </div>

      <span class="shrink-0 text-xs text-muted-soft">{{ $viewModel->progressLabel() }}</span>
    </div>

    <ul class="overflow-hidden rounded-xl border border-hairline bg-canvas">
      @foreach ($viewModel->steps() as $step)
        <li class="flex items-start gap-3 px-4 py-3.5 transition-colors duration-150 not-first:border-t not-first:border-hairline-soft hover:bg-hover">
          <span
            class="mt-px flex size-4.5 shrink-0 items-center justify-center rounded-sm border {{ $step['done'] ? 'border-primary bg-primary text-on-primary' : 'border-hairline-strong bg-input text-transparent' }}"
            aria-hidden="true"
          >
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2.5 6.3 4.8 8.6 9.5 3.8"></path>
            </svg>
          </span>

          <div class="min-w-0 flex-1 space-y-0.5">
            <p class="text-sm font-semibold {{ $step['done'] ? 'text-muted line-through' : 'text-ink' }}">
              {{ $step['title'] }}

              <span class="sr-only">{{ $step['done'] ? __('Done') : __('Not done yet') }}</span>
            </p>

            <p class="text-[13px] leading-relaxed text-pretty text-muted">{{ $step['description'] }}</p>
          </div>

          @if (! $step['done'] && $step['url'])
            <a href="{{ $step['url'] }}" data-turbo="true" class="mt-px shrink-0 text-[13px] font-semibold text-brand hover:underline">{{ $step['action'] }}</a>
          @endif
        </li>
      @endforeach
    </ul>
  </div>

  <div class="flex max-w-160 items-start gap-3 rounded-lg border border-dashed border-hairline-strong bg-sunken px-4 py-3.5">
    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="mt-px shrink-0 text-muted" aria-hidden="true">
      <circle cx="8" cy="8" r="6.2"></circle>
      <line x1="8" y1="7.2" x2="8" y2="11.2"></line>
      <line x1="8" y1="4.9" x2="8" y2="5"></line>
    </svg>

    <p class="text-[13px] leading-relaxed text-pretty text-muted">{{ $viewModel->importNote() }}</p>
  </div>
</x-account-layout>
