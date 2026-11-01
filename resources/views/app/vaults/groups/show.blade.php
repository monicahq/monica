{{-- One group: what it is, the people in it today, and those who left. --}}
{{-- @var \App\ViewModels\Vaults\GroupViewModel $viewModel --}}
@php
  $vault = $viewModel->vault();
  $members = $viewModel->members();
@endphp
<x-shell :title="$viewModel->name()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$vault" :items="[['label' => __('Groups'), 'url' => $viewModel->indexUrl()], ['label' => $viewModel->name()]]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$vault" current="groups" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->name()" :description="$viewModel->period()">
    <x-slot:actions>
      <x-button.secondary :href="$viewModel->editUrl()" data-turbo="true" size="small">{{ __('Edit the group') }}</x-button.secondary>

      <x-button :href="$viewModel->addMemberUrl()" data-turbo="true" size="small">
        <x-slot:icon>
          <x-icon name="plus" :size="16" :stroke-width="1.6" />
        </x-slot:icon>

        {{ __('Add somebody') }}
      </x-button>

      @if ($viewModel->vCardUrl() !== null)
        <x-actions-menu :label="__('More actions for :name', ['name' => $viewModel->name()])">
          <x-actions-menu.item :href="$viewModel->vCardUrl()" download data-no-instant>
            {{ __('Export as vCard') }}
          </x-actions-menu.item>
        </x-actions-menu>
      @endif
    </x-slot:actions>
  </x-page-header>

  @if ($viewModel->description() !== null)
    <p class="max-w-reading-narrow text-sm text-pretty whitespace-pre-line text-body">{{ $viewModel->description() }}</p>
  @endif

  <x-list-panel :items="$members" :paginator="$members" :size="$viewModel->size()" :default="$viewModel->defaultSize()">
    @foreach ($members as $member)
      <div class="flex min-w-0 items-center gap-3 border-b border-hairline px-4 py-2.75 last:border-b-0">
        <x-contact-avatar :name="$member['name']" :size="32" />

        <span class="min-w-0 flex-1">
          <a href="{{ $member['contactUrl'] }}" data-turbo="true" class="block truncate text-base font-semibold text-ink no-underline hover:underline focus-ring">{{ $member['name'] }}</a>

          @if ($member['role'] !== null || $member['period'] !== null)
            <span class="block truncate text-xs text-muted">{{ collect([$member['role'], $member['period']])->filter()->implode(' · ') }}</span>
          @endif
        </span>

        @if (! $member['isCurrent'])
          <x-badge>{{ __('Left') }}</x-badge>
        @endif

        @if ($member['isOutsideTheGroupDates'])
          <x-badge tone="warning">{{ __('Outside the dates of the group') }}</x-badge>
        @endif

        <x-button.ghost :href="$member['editUrl']" data-turbo="true" size="small" :aria-label="__('Edit the membership of :name', ['name' => $member['name']])">{{ __('Edit') }}</x-button.ghost>
      </div>
    @endforeach

    <x-slot:empty>
      <x-empty-state
        :title="__('Nobody in this group yet')"
        :href="$viewModel->addMemberUrl()"
        :link-label="__('Add somebody')"
      />
    </x-slot:empty>
  </x-list-panel>
</x-shell>
