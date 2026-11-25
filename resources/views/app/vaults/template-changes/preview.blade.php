{{-- Step two of a change of template: what moving the records would do, before anything is moved. --}}
{{-- @var \App\ViewModels\Vaults\TemplateChangePreviewViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $details = $viewModel->details();
  $blocked = $viewModel->blocked();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="$viewModel->breadcrumb()" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" :current="$viewModel->sidebarCurrent()" />
  </x-slot:sidebar>

  <x-steps :steps="$viewModel->steps()" :current="2" />

  <x-page-header>
    {{ $viewModel->fromName() }}
    <x-icon name="arrow-right" :size="20" aria-hidden="true" class="inline text-muted" />
    <span class="sr-only">{{ __('to') }}</span>
    {{ $viewModel->toName() }}
  </x-page-header>

  <p id="template-change-ready" class="text-body">{{ $viewModel->readySentence() }}</p>

  <div class="flex max-w-reading flex-col gap-6">
    @if ($blocked !== [])
      <x-notice tone="warning" :title="$viewModel->blockedReason()">
        <ul class="list-disc pl-5">
          @foreach ($blocked as $name)
            <li>{{ $name }}</li>
          @endforeach
        </ul>

        <span class="mt-1 block">{{ $viewModel->remainingSentence() }}</span>
      </x-notice>
    @endif

    <x-detail-list label-width="minmax(0,1fr)">
      @foreach ($viewModel->effects() as $effect)
        <x-detail-list.row :label="$effect['label']">
          <span class="block text-right font-semibold tabular-nums">{{ $effect['value'] }}</span>
        </x-detail-list.row>
      @endforeach
    </x-detail-list>

    <x-notice tone="info">{{ __('Monica keeps these values. They can reappear if a later template includes a matching field.') }}</x-notice>

    @if ($viewModel->hiddenSentence() !== null)
      <p class="text-sm text-muted">{{ $viewModel->hiddenSentence() }}</p>
    @endif

    @if ($details !== [])
      <div x-data="{ open: false }" class="rounded-lg border border-hairline bg-card">
        <button
          type="button"
          @click="open = ! open"
          :aria-expanded="open ? 'true' : 'false'"
          aria-controls="template-change-details"
          class="flex w-full cursor-pointer items-center justify-between gap-3 px-4 py-3 text-left text-sm font-medium text-ink focus-ring-inset"
        >
          {{ __('Details by field') }}
          <x-icon name="chevron-down" :size="14" aria-hidden="true" class="text-muted" ::class="open && 'rotate-180'" />
        </button>

        <ul id="template-change-details" x-show="open" x-cloak class="border-t border-hairline">
          @foreach ($details as $row)
            <li class="flex items-center justify-between gap-3 border-b border-hairline px-4 py-2.5 text-sm last:border-b-0">
              <span class="text-ink">{{ $row['label'] }}</span>
              <x-badge>{{ $row['effect'] }}</x-badge>
            </li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="post" action="{{ $viewModel->createUrl() }}" class="flex flex-wrap items-center justify-end gap-3">
      @csrf
      @foreach ($viewModel->parameters() as $parameter => $value)
        @if ($value !== null)
          <input type="hidden" name="{{ $parameter }}" value="{{ $value }}" />
        @endif
      @endforeach

      <x-button :href="$viewModel->backUrl()" type="button" variant="secondary" data-turbo="true">{{ __('Back') }}</x-button>
      @if ($viewModel->canMove())
        <x-button type="submit">{{ $viewModel->moveLabel() }}</x-button>
      @else
        <x-button type="button" aria-disabled="true" aria-describedby="template-change-ready">{{ $viewModel->moveLabel() }}</x-button>
      @endif
    </form>
  </div>
</x-shell>
