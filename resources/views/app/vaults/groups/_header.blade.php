{{-- The top of every tab of a group: its name, who belongs to it, since when, and what may be done. --}}
{{-- @var \App\ViewModels\Vaults\GroupViewModel $viewModel --}}
@php
  $avatars = $viewModel->avatarNames();
@endphp
<x-page-header :title="$viewModel->name()">
  <x-slot:actions>
    <x-button.secondary :href="$viewModel->editUrl()" data-turbo="true" size="small">{{ __('Edit the group') }}</x-button.secondary>

    <x-button.secondary :href="$viewModel->addMemberUrl()" data-turbo="true" size="small">
      <x-slot:icon>
        <x-icon name="plus" :size="16" :stroke-width="1.6" />
      </x-slot:icon>

      {{ __('Add somebody') }}
    </x-button.secondary>

    @if ($viewModel->vCardUrl() !== null)
      <x-actions-menu :label="__('More actions for :name', ['name' => $viewModel->name()])">
        <x-actions-menu.item :href="$viewModel->vCardUrl()" download data-no-instant>
          {{ __('Export as vCard') }}
        </x-actions-menu.item>
      </x-actions-menu>
    @endif
  </x-slot:actions>
</x-page-header>

<div class="-mt-2 flex flex-col gap-3">
  @if ($viewModel->description() !== null)
    <p class="max-w-reading-narrow text-base text-pretty whitespace-pre-line text-body">{{ $viewModel->description() }}</p>
  @endif

  <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-body">
    <span class="flex items-center gap-2.5">
      @if ($avatars !== [])
        <span class="flex pl-1.5" aria-hidden="true">
          @foreach ($avatars as $name)
            <span class="-ml-1.5 flex rounded-full ring-2 ring-page"><x-contact-avatar :name="$name" :size="26" /></span>
          @endforeach
        </span>
      @endif

      <span>{{ $viewModel->headcountLine() }}</span>
    </span>

    @if ($viewModel->period() !== null)
      <span class="flex items-center gap-1.75">
        <span class="text-muted" aria-hidden="true"><x-icon name="calendar" :size="15" /></span>
        {{ $viewModel->period() }}
      </span>
    @endif
  </div>
</div>
