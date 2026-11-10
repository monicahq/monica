{{-- The members tab of a group: the people in it today, those who left, and how often each came. --}}
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

  @include('app.vaults.groups._header', ['viewModel' => $viewModel])

  <div class="flex flex-col gap-6">
    @include('app.vaults.groups._tabs', ['viewModel' => $viewModel, 'current' => 'members'])

    <x-list-panel :items="$members" :paginator="$members" :size="$viewModel->size()" :default="$viewModel->defaultSize()">
      @foreach ($members as $member)
        <div class="flex min-w-0 flex-wrap items-center gap-3 border-b border-hairline px-4 py-2.75 last:border-b-0">
          <x-contact-avatar :name="$member['name']" :size="32" />

          <span class="min-w-0 flex-1">
            <a href="{{ $member['contactUrl'] }}" data-turbo="true" class="block text-base font-semibold break-words text-ink no-underline hover:underline focus-ring">{{ $member['name'] }}</a>

            <span class="block text-xs text-pretty text-muted">{{ collect([$member['role'], $member['period'], $member['participation']])->filter()->implode(' · ') }}</span>
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
  </div>
</x-shell>
