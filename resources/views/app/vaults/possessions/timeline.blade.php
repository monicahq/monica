{{-- The Timeline section of a possession: everything that happened to it, read from its sections. --}}
{{-- @var \App\ViewModels\Vaults\PossessionTimelineViewModel $viewModel --}}
@php
  $page = $viewModel->page();
  $vault = $page->vault();
  $chips = $viewModel->chips();
  $groups = $viewModel->groups();
@endphp

<x-shell :title="__('Timeline of :name', ['name' => $page->name()])" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$page->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$page->user()" :vault="$vault" current="possessions" />
  </x-slot:sidebar>

  @include('app.vaults.possessions._header', ['page' => $page])

  <div class="grid grid-cols-1 items-start gap-x-8 gap-y-6 lg:grid-cols-[13.5rem_minmax(0,1fr)]">
    @include('app.vaults.possessions._sections', ['page' => $page, 'current' => 'timeline'])

    <section aria-labelledby="timeline-title" class="flex min-w-0 flex-col gap-5">
      <div class="flex min-w-65 flex-1 flex-col gap-1">
        <div class="flex items-center gap-2">
          <h2 id="timeline-title" class="text-section-title text-ink">{{ __('Timeline') }}</h2>
          <x-help
            :label="__('Help about the timeline')"
            :title="__('How the timeline works')"
            guide="customise.possession-timeline"
          >
            {{ __('Each entry is read from the section it was recorded in. Nothing is stored on the timeline itself, so to correct an entry, open it in its section.') }}
          </x-help>
        </div>
        <p class="text-sm text-pretty text-muted">{{ __('Everything that happened to this possession, gathered from its other sections and kept in order.') }}</p>
      </div>

      @if ($chips !== [])
        <div role="group" aria-label="{{ __('Which entries to show') }}" class="flex flex-wrap items-center gap-2">
          @foreach ($chips as $chip)
            <x-chip :href="$chip['url']" :selected="$chip['isCurrent']" :count="$chip['count']">{{ $chip['label'] }}</x-chip>
          @endforeach
        </div>
      @endif

      @forelse ($groups as $group)
        <div class="flex flex-col gap-3">
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h3 class="text-card-title text-ink">{{ $group['title'] }}</h3>
            <span class="text-sm text-muted">{{ $group['count'] }}</span>
          </div>

          <ol role="list" class="flex list-none flex-col">
            @foreach ($group['entries'] as $entry)
              @include('app.vaults.possessions._timeline-entry', ['entry' => $entry, 'isLast' => $loop->last])
            @endforeach
          </ol>
        </div>
      @empty
        <p class="rounded-lg border border-dashed border-hairline-strong px-4 py-5 text-sm text-muted">{{ $viewModel->emptyLine() }}</p>
      @endforelse
    </section>
  </div>
</x-shell>
