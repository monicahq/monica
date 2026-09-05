{{--
  Somebody's page as whoever holds a link to it sees it.

  It is not the private record with things taken off it. The bar at the top is dark
  and says who shared this and on what terms, so the page never reads as an
  application somebody has been let into: it is a document, and it is signed.

  There is no photograph, no dock, no search and no way anywhere else. The avatar is
  drawn from initials rather than from the file, because a file is a file and files
  stay behind whatever a link says.

  Nothing here is a form. That is the rule the whole feature rests on, and the reason
  this file has no <x-form> in it and never may.

  The tabs are the pages that still have something on them. A page whose every
  section was held back is not a tab at all: an empty tab would tell somebody that
  there is something they are not being shown, which is a leak of a kind even though
  it carries no value.

  @var \App\ViewModels\Shared\SharedContactViewModel $viewModel
--}}
@php
  $tabs = $viewModel->tabs();
  $sections = $viewModel->sections();
@endphp
<x-guest-layout :title="$viewModel->title()">
  <div class="flex min-h-screen flex-col bg-page">
    <header class="flex items-center gap-3 bg-ink px-6 py-3">
      <x-logo :size="24" class="invert dark:invert-0" />

      <span class="min-w-0 truncate text-sm text-page/70">{{ $viewModel->sharedBy() }}</span>

      <span class="ml-auto flex shrink-0 items-center gap-2 rounded-sm bg-page/12 px-2.5 py-1 text-xs text-page">
        <x-icon name="lock" :size="14" :stroke-width="1.7" />
        {{ $viewModel->terms() }}
      </span>
    </header>

    <main class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-6 py-10">
      <div class="flex flex-wrap items-center gap-4.5">
        <x-avatar-initials :name="$viewModel->title()" :size="68" />

        <div class="flex min-w-0 flex-col gap-1.5">
          <h1 class="text-page-title text-balance text-ink">{{ $viewModel->title() }}</h1>

          @if ($viewModel->headerFacts() !== [])
            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-base text-muted">
              @foreach ($viewModel->headerFacts() as $fact)
                <span>{{ $fact['label'] }} <b class="font-semibold text-body">{{ $fact['value'] }}</b></span>

                @if (! $loop->last)
                  <span aria-hidden="true">&middot;</span>
                @endif
              @endforeach
            </p>
          @endif
        </div>
      </div>

      @if ($tabs !== [])
        <div role="tablist" class="flex flex-wrap items-end gap-1 border-b border-hairline">
          @foreach ($tabs as $tab)
            <a
              href="{{ $tab['url'] }}"
              role="tab"
              aria-selected="{{ $tab['isSelected'] ? 'true' : 'false' }}"
              @class([
                '-mb-px px-3.25 py-2.75 text-nav transition-colors duration-(--duration-fast) ease-out',
                'border-b-2 border-primary text-ink' => $tab['isSelected'],
                'border-b-2 border-transparent text-muted hover:text-ink' => ! $tab['isSelected'],
              ])
            >{{ $tab['label'] }}</a>
          @endforeach
        </div>
      @endif

      @forelse ($sections as $section)
        <x-panel :title="$section['name']" :icon="$section['icon']" :tint="$section['tint']" :flush="true">
          <dl class="grid gap-0">
            @foreach ($section['fields'] as $field)
              <div class="grid grid-cols-[minmax(0,170px)_minmax(0,1fr)] items-baseline gap-4 border-b border-hairline px-4.5 py-3 last:border-b-0 max-[560px]:grid-cols-1 max-[560px]:gap-0.5">
                <dt class="text-xs tracking-(--tracking-caption) text-muted uppercase">{{ $field['label'] }}</dt>

                <dd class="text-base text-pretty text-ink">{{ $field['value'] }}</dd>
              </div>
            @endforeach
          </dl>
        </x-panel>
      @empty
        <x-panel>
          <p class="text-base text-muted">{{ $viewModel->emptyLine() }}</p>
        </x-panel>
      @endforelse

      <x-notice tone="quiet" icon="info">
        {{ $viewModel->closingLine() }}
      </x-notice>
    </main>

    <p class="px-6 pb-8 text-center text-xs text-muted-soft">{{ config('app.name') }}</p>
  </div>
</x-guest-layout>
