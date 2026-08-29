{{--
  Every marketplace submission waiting for a decision, oldest first.

  Oldest first is not a preference. A queue ordered any other way is a queue where
  the author who has waited longest is the one nobody reaches, and waiting without
  an answer is the thing this screen exists to end.

  Each row says how long it has waited out loud, because a date does not read as a
  delay and a delay is what a moderator has to act on.

  @var \App\ViewModels\Admin\ModerationQueueViewModel $viewModel
--}}
<x-admin-layout :title="__('Moderation')">
  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Moderation') }}</h1>

    <p class="max-w-[64ch] text-base text-pretty text-body">
      @if ($viewModel->waitingCount() === 0)
        {{ __('Nothing is waiting for a decision.') }}
      @else
        {{ trans_choice(':count submission is waiting for a decision.|:count submissions are waiting for a decision.', $viewModel->waitingCount()) }}
      @endif
    </p>
  </header>

  <x-panel :flush="true">
    <div id="submissions">
      @forelse ($viewModel->submissions() as $submission)
        <div class="flex flex-col gap-2 border-b border-hairline px-4 py-3.5 last:border-b-0 sm:flex-row sm:items-center sm:gap-4">
          <div class="grow">
            <a href="{{ route('admin.moderation.show', ['version' => $submission->id]) }}" data-turbo="true" class="font-semibold text-ink no-underline hover:underline">
              {{ $submission->listing->name }}
            </a>

            <p class="text-sm text-muted">
              {{ $submission->listing->author_name }}
              &middot; {{ $submission->listing->category->name }}
              &middot; {{ __('version :version', ['version' => $submission->version_number]) }}
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
      @empty
        <x-empty-state :title="__('Nothing is waiting')">
          <x-slot:icon>
            <x-icon name="gift" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('Every submission has been decided. When somebody sends a template or a section in, it appears here.') }}
        </x-empty-state>
      @endforelse
    </div>

    @if ($viewModel->submissions()->hasMorePages())
      <x-slot:footer>
        <div class="text-center">
          <x-link :href="$viewModel->submissions()->nextPageUrl()">{{ __('Load more') }}</x-link>
        </div>
      </x-slot:footer>
    @endif
  </x-panel>
</x-admin-layout>
