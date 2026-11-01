{{-- The groups of one vault, such as households and teams, on one list. --}}
{{-- @var \App\ViewModels\Vaults\GroupsViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $groups = $viewModel->groups();
@endphp
<x-shell :title="__('Groups')" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('Groups')]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="groups" />
  </x-slot:sidebar>

  <x-page-header :title="__('Groups')" :description="__('Households, couples, teams and circles of friends: the people who belong together.')">
    <x-slot:actions>
      <x-button :href="$viewModel->addUrl()" data-turbo="true" size="small">
        <x-slot:icon>
          <x-icon name="plus" :size="16" :stroke-width="1.6" />
        </x-slot:icon>

        {{ __('Add a group') }}
      </x-button>
    </x-slot:actions>
  </x-page-header>

  @if ($deleted = $viewModel->deletedGroup())
    <div x-data="{ shown: true }" x-show="shown">
      <x-notice tone="quiet" icon="check-circle">
        {{ $deleted['line'] }}

        <x-slot:action>
          <x-form method="put" :action="$deleted['restoreUrl']">
            <x-button.secondary size="small" x-autofocus>{{ __('Undo') }}</x-button.secondary>
          </x-form>
        </x-slot:action>
      </x-notice>
    </div>
  @endif

  <x-list-panel :items="$groups" :paginator="$groups" :size="$viewModel->size()" :default="$viewModel->defaultSize()">
    @foreach ($groups as $group)
      <a href="{{ $group['url'] }}" data-turbo="true" class="flex min-w-0 items-center gap-3 border-b border-hairline px-4 py-2.75 no-underline last:border-b-0 hover:bg-hover focus-ring-inset">
        <x-icon name="people" :size="18" :stroke-width="1.5" class="shrink-0 text-muted" />

        <span class="min-w-0 flex-1">
          <span class="block truncate text-base font-semibold text-ink">{{ $group['name'] }}</span>

          @if ($group['period'] !== null)
            <span class="block truncate text-xs text-muted">{{ $group['period'] }}</span>
          @endif
        </span>

        <span class="shrink-0 text-sm text-muted">{{ $group['headcount'] }}</span>
      </a>
    @endforeach

    <x-slot:empty>
      <x-empty-state
        :title="__('No group in this vault yet')"
        :href="$viewModel->addUrl()"
        :link-label="__('Add a group')"
      />
    </x-slot:empty>
  </x-list-panel>
</x-shell>
