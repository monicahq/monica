{{-- The records of one entity type in one vault, on one list. --}}
{{-- @var \App\ViewModels\Vaults\EntitiesViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $entities = $viewModel->entities();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => $viewModel->title()]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" :current="$viewModel->current()" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" />

  @unless ($viewModel->isActive())
    <x-notice>{{ __('This type is turned off. You can view and edit the existing profiles, but you cannot add new ones.') }}</x-notice>
  @endunless

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
      <x-empty-state :title="$viewModel->emptyTitle()" />
    </x-slot:empty>
  </x-list-panel>
</x-shell>
