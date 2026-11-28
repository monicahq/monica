{{-- What changed in Monica, day by day, with a way to say which changes help and which do not. --}}
{{-- @var \App\ViewModels\ChangelogViewModel $viewModel --}}
@php
  $figures = $viewModel->figures();
  $days = $viewModel->days();
  $feedback = $viewModel->allowsFeedback();
@endphp
<x-account-layout :title="__('What’s new')">
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[['label' => __('What’s new')]]" />
  </x-slot:breadcrumb>

  <div class="flex items-center justify-between gap-6">
    <x-page-header :title="__('What’s new')" :description="$feedback ? __('What changed in Monica, day by day. Tell us which changes help you and which do not.') : __('What changed in Monica, day by day.')" />

    <img
      src="{{ asset('images/panda-whats-new.webp') }}"
      alt=""
      width="360"
      height="360"
      decoding="async"
      class="size-24 flex-none sm:size-45 dark:hidden"
    />

    <img
      src="{{ asset('images/panda-whats-new-dark.webp') }}"
      alt=""
      width="360"
      height="360"
      decoding="async"
      class="hidden size-24 flex-none sm:size-45 dark:block"
    />
  </div>

  <div class="flex flex-col gap-2.5">
    <nav aria-label="{{ __('Period') }}" class="flex gap-0.5 self-start rounded-lg border border-hairline bg-card p-0.5">
      @foreach ($viewModel->periods() as $period)
        <a
          href="{{ $period['url'] }}"
          @if ($period['current']) aria-current="page" @endif
          @class([
            'focus-ring flex h-7 items-center rounded-md border px-3 text-sm font-medium no-underline',
            'border-hairline bg-canvas text-ink shadow-panel' => $period['current'],
            'border-transparent text-muted hover:text-ink' => ! $period['current'],
          ])
        >{{ $period['label'] }}</a>
      @endforeach
    </nav>

    <dl class="grid grid-cols-2 overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel sm:grid-cols-4">
      @foreach ([
        [$figures['days'], __('Days with changes')],
        [$figures['features'], __('New features')],
        [$figures['improvements'], __('Improvements')],
        [$figures['fixes'], __('Bugs fixed')],
      ] as [$figure, $label])
        <div @class(['flex flex-col-reverse gap-1 px-4 py-3.5', 'border-s border-hairline' => ! $loop->first, 'max-sm:border-s-0 max-sm:border-t' => $loop->index === 2, 'max-sm:border-t' => $loop->index === 3])>
          <dt class="text-sm text-muted">{{ $label }}</dt>
          <dd class="text-xl font-semibold tracking-tight text-ink">{{ $figure }}</dd>
        </div>
      @endforeach
    </dl>
  </div>

  @if ($days === [])
    <p class="text-base text-body">{{ __('Nothing changed in this period.') }}</p>
  @endif

  @foreach ($days as $day)
    <section aria-labelledby="changelog-day-{{ $day['date'] }}" class="flex flex-col gap-6">
      @if ($day['showsSeenDivider'])
        <div class="flex items-center gap-3">
          <x-caption tone="muted">{{ __('Seen on your last visit') }}</x-caption>
          <span class="h-px flex-1 bg-hairline" aria-hidden="true"></span>
        </div>
      @endif

      <div @class(['grid grid-cols-1 gap-4 md:grid-cols-[180px_minmax(0,1fr)] md:gap-8', 'border-t border-hairline pt-6' => ! $day['showsSeenDivider'] && ! $loop->first])>
        <h2 id="changelog-day-{{ $day['date'] }}" class="flex flex-col gap-0.5 self-start md:sticky md:top-4">
          <span class="text-base font-semibold text-ink">{{ $day['label'] }}</span>
          <time datetime="{{ $day['date'] }}" class="text-sm font-normal text-muted">{{ $day['dateLabel'] }}</time>
        </h2>

        <div class="flex min-w-0 flex-col gap-8">
          @foreach ($day['entries'] as $entry)
            @include('app.changelog._entry', ['entry' => $entry, 'first' => $loop->first, 'feedback' => $feedback])
          @endforeach

          @if ($day['fixes'] !== [])
            @include('app.changelog._fixes', ['fixes' => $day['fixes'], 'heading' => $day['fixesHeading'], 'feedback' => $feedback])
          @endif
        </div>
      </div>
    </section>
  @endforeach
</x-account-layout>
