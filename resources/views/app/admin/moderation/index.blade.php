{{-- Every marketplace submission waiting for a decision, oldest first. --}}
{{-- @var \App\ViewModels\Admin\ModerationQueueViewModel $viewModel --}}
<x-admin-layout :title="__('Moderation')">
  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Moderation') }}</h1>

    <p class="max-w-prose text-base text-pretty text-body">
      @if ($viewModel->waitingCount() === 0)
        {{ __('Nothing is waiting for a decision.') }}
      @else
        {{ trans_choice(':count submission is waiting for a decision.|:count submissions are waiting for a decision.', $viewModel->waitingCount()) }}
      @endif
    </p>
  </header>

  <x-list-panel
    :items="$viewModel->submissions()"
    id="submissions"
    :next-page-url="$viewModel->submissions()->hasMorePages() ? $viewModel->submissions()->nextPageUrl() : null"
  >
    <x-slot:empty>
      <x-empty-state :title="__('Nothing is waiting')">
        <x-slot:icon>
          <x-icon name="gift" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('Every submission has been decided. When somebody sends a template or a section in, it appears here.') }}
      </x-empty-state>
    </x-slot:empty>

    @foreach ($viewModel->submissions() as $submission)
      <div class="flex flex-col gap-2 border-b border-hairline px-4 py-3.5 last:border-b-0 sm:flex-row sm:items-center sm:gap-4">
        <div class="grow">
          <a href="{{ route('admin.moderation.show', ['version' => $submission->id]) }}" data-turbo="true" class="font-semibold text-ink no-underline hover:underline">
            {{ $submission->listing->name }}
          </a>

          <p class="text-sm text-muted">
            {{ $submission->listing->author_name }}
            ⋅ {{ $submission->listing->category->name }}
            ⋅ {{ __('version :version', ['version' => $submission->version_number]) }}
          </p>
        </div>

        <div class="shrink-0 text-sm text-muted sm:text-right">
          <p>{{ $submission->created_at->isoFormat('LL') }}</p>
          <p class="text-xs">{{ __('waiting :duration', ['duration' => $submission->created_at->diffForHumans(syntax: true)]) }}</p>
        </div>

        <x-button.secondary :href="route('admin.moderation.show', ['version' => $submission->id])" data-turbo="true" size="small" class="shrink-0">
          {{ __('Read it') }}
        </x-button.secondary>
      </div>
    @endforeach
  </x-list-panel>
</x-admin-layout>
