{{-- The records of one entity type in one vault, on one list. --}}
{{-- @var \App\ViewModels\Vaults\EntitiesViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $entities = $viewModel->entities();
  $addRefusal = $viewModel->addRefusal();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => $viewModel->title()]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" :current="$viewModel->current()" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()">
    <x-slot:actions>
      <x-button
        :href="$addRefusal === null ? $viewModel->addUrl() : null"
        type="button"
        :aria-disabled="$addRefusal !== null ? 'true' : null"
        :aria-describedby="$addRefusal !== null ? 'entity-add-refusal' : null"
        data-turbo="true"
        size="small"
      >
        <x-slot:icon>
          <x-icon name="plus" :size="16" :stroke-width="1.6" />
        </x-slot:icon>

        {{ $viewModel->addLabel() }}
      </x-button>
    </x-slot:actions>
  </x-page-header>

  @if (! $viewModel->isActive())
    <x-notice id="entity-add-refusal">{{ __('This type is turned off. You can view and edit the existing profiles, but you cannot add new ones.') }}</x-notice>
  @elseif ($addRefusal !== null)
    <p id="entity-add-refusal" class="max-w-reading-narrow text-sm text-pretty text-muted">{{ $addRefusal }}</p>
  @endif

  <x-list-panel :items="$entities">
    @foreach ($entities as $entity)
      <div class="flex min-w-0 items-center gap-3 border-b border-hairline px-4 py-2.75 last:border-b-0">
        <x-contact-avatar :name="$entity['name']" :url="$entity['avatarUrl']" :size="32" />

        <span class="truncate text-base font-semibold text-ink">{{ $entity['name'] }}</span>

        @if ($entity['isArchived'])
          <x-badge>{{ __('Archived') }}</x-badge>
        @endif
      </div>
    @endforeach

    <x-slot:empty>
      <x-empty-state
        :title="$viewModel->emptyTitle()"
        :href="$addRefusal === null ? $viewModel->addUrl() : null"
        :link-label="$viewModel->addLabel()"
      />
    </x-slot:empty>
  </x-list-panel>
</x-shell>
