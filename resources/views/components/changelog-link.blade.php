{{-- The link to What’s new in the top bar, with the number of changes the person has not seen. --}}
{{-- @var \App\ViewModels\ChangelogLinkViewModel|null $viewModel --}}
@if ($viewModel !== null)
  <x-button.ghost :href="route('changelog.index')" size="top-bar" :aria-label="$viewModel->label()">
    <x-slot:icon>
      <x-icon name="activity" :size="16" :stroke-width="1.4" />
    </x-slot:icon>

    <span class="hidden topbar-labels:inline">{{ __('What’s new') }}</span>

    @if ($viewModel->unseenCount() > 0)
      <span class="inline-flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-primary px-1.25 text-2xs font-semibold text-on-primary" aria-hidden="true">{{ $viewModel->unseenCount() }}</span>
    @endif
  </x-button.ghost>
@endif
