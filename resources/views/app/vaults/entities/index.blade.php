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

  <x-list-panel :items="$entities" :paginator="$entities" :size="$viewModel->size()" :default="$viewModel->defaultSize()">
    @foreach ($entities as $entity)
      <a
        href="{{ $entity['url'] }}"
        data-turbo="true"
        class="flex min-w-0 items-center gap-3 border-b border-hairline px-4 py-2.75 no-underline transition-colors duration-(--duration-fast) ease-out last:border-b-0 hover:bg-hover focus-ring-inset"
      >
        <x-contact-avatar :name="$entity['name']" :url="$entity['avatarUrl']" :size="32" alt="" />

        <span class="truncate text-base font-semibold text-ink">{{ $entity['name'] }}</span>

        @if ($entity['isArchived'])
          <x-badge>{{ __('Archived') }}</x-badge>
        @endif
      </a>
    @endforeach

    <x-slot:empty>
      <div aria-hidden="true">
        @foreach ([['opacity-90', 'w-30', 'w-18'], ['opacity-55', 'w-22.5', 'w-14'], ['opacity-25', 'w-35', 'w-16']] as [$fade, $nameWidth, $dateWidth])
          <div class="flex items-center gap-3 border-b border-hairline px-4 py-3.5 {{ $fade }}">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full" style="color: {{ $viewModel->colour() }}; background: color-mix(in srgb, {{ $viewModel->colour() }} 14%, var(--canvas))">
              <x-entity-icon :name="$viewModel->icon()" :size="16" />
            </span>
            <span class="h-2.5 rounded bg-hairline {{ $nameWidth }}"></span>
            <span class="flex-1"></span>
            <span class="h-2 rounded bg-hairline {{ $dateWidth }}"></span>
          </div>
        @endforeach
      </div>

      <x-empty-state :title="$viewModel->emptyTitle()">
        {{ $viewModel->emptyDescription() }}

        <x-slot:action>
          <x-button
            :href="$addRefusal === null ? $viewModel->addUrl() : null"
            type="button"
            variant="secondary"
            :aria-disabled="$addRefusal !== null ? 'true' : null"
            :aria-describedby="$addRefusal !== null ? 'entity-add-refusal' : null"
            data-turbo="true"
          >
            {{ $viewModel->firstAddLabel() }}
          </x-button>
        </x-slot:action>
      </x-empty-state>
    </x-slot:empty>
  </x-list-panel>
</x-shell>
