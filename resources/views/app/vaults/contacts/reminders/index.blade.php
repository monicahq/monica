{{-- Every reminder about one contact: what is waiting for an answer, what is coming, what was answered. --}}
{{-- @var \App\ViewModels\Vaults\ContactRemindersViewModel $viewModel --}}
@php
  $groups = $viewModel->groups();
  $summary = $viewModel->summary();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => route('contacts.index', $viewModel->vault())],
        ['label' => $viewModel->contact()->name, 'url' => $viewModel->contactUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <a href="{{ $viewModel->contactUrl() }}" data-turbo="true" class="flex w-fit items-center gap-1.5 rounded-xs text-sm text-muted no-underline hover:text-ink focus-ring">
    <x-icon name="chevron-right" :size="15" :stroke-width="1.6" class="rotate-180" />

    {{ __('Back to :name', ['name' => $viewModel->contact()->name]) }}
  </a>

  <x-page-header :title="$viewModel->title()">
    <x-slot:actions>
      @if ($viewModel->canAdd())
        <x-button :href="$viewModel->newUrl()" data-turbo="true" size="small">
          <x-slot:icon>
            <x-icon name="plus" :size="15" :stroke-width="1.6" />
          </x-slot:icon>

          {{ __('Add a reminder') }}
        </x-button>
      @endif
    </x-slot:actions>
  </x-page-header>

  <div id="contact-reminders" class="flex flex-col gap-5">
    @if ($summary->hasAny())
      @include('app.vaults.contacts.reminders._summary', ['summary' => $summary])
    @endif

    <div role="group" aria-label="{{ __('Show') }}" class="flex flex-wrap gap-2">
      @foreach ($viewModel->filters() as $filter)
        <x-chip
          :href="$filter['url']"
          :selected="$filter['isCurrent']"
          :count="$filter['count']"
          x-target.push="contact-reminders"
        >{{ $filter['label'] }}</x-chip>
      @endforeach
    </div>

    @forelse ($groups as $group)
      <section class="flex flex-col gap-2.5" aria-labelledby="reminders-{{ $group['key'] }}">
        <div class="flex items-baseline gap-2.5 px-0.5">
          <h2 id="reminders-{{ $group['key'] }}" class="text-sm font-semibold text-ink">{{ $group['title'] }}</h2>

          @if ($group['note'] !== '')
            <span class="text-sm text-muted">{{ $group['note'] }}</span>
          @endif
        </div>

        @if ($group['key'] === 'history')
          <x-list-panel
            :items="$group['rows']"
            id="contact-reminders-rows"
            merge="append"
            target="contact-reminders-rows contact-reminders-footer"
            footer-id="contact-reminders-footer"
            :next-page-url="$viewModel->nextPageUrl()"
            :shows-end="$viewModel->page() > 1"
          >
            @foreach ($group['rows'] as $row)
              @include('app.vaults.contacts.reminders._row', ['row' => $row, 'answered' => true])
            @endforeach

            <x-slot:empty></x-slot:empty>
          </x-list-panel>
        @else
          <div class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
            @foreach ($group['rows'] as $row)
              @include('app.vaults.contacts.reminders._row', ['row' => $row, 'answered' => false])
            @endforeach
          </div>
        @endif
      </section>
    @empty
      <div class="flex flex-col items-center gap-1.5 rounded-lg border border-dashed border-hairline-strong px-6 py-10 text-center">
        <span class="font-semibold text-ink">{{ $viewModel->emptyTitle() }}</span>

        <span class="max-w-[48ch] text-pretty text-muted">{{ $viewModel->emptyLine() }}</span>
      </div>
    @endforelse
  </div>
</x-shell>
