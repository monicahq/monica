{{-- One submission, with everything its author sent in. --}}
{{-- @var \App\ViewModels\Admin\ModerationSubmissionViewModel $viewModel --}}
<x-admin-layout :title="$viewModel->title()">
  <header class="flex flex-col gap-2">
    <x-link :href="route('admin.moderation.index')" data-turbo="true">{{ __('Back to moderation') }}</x-link>

    <h1 class="text-page-title text-ink">{{ $viewModel->title() }}</h1>

    <p class="text-sm text-muted">{{ $viewModel->provenance() }}</p>
  </header>

  @error('decision')
    <x-notice tone="danger">{{ $message }}</x-notice>
  @enderror

  <x-panel :title="__('What the author says')">
    <div class="flex flex-col gap-4">
      <div>
        <p class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Summary') }}</p>
        <p class="text-base text-pretty text-body">{{ $viewModel->summary() }}</p>
      </div>

      <div>
        <p class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Description') }}</p>
        <p class="text-base text-pretty whitespace-pre-line text-body">{{ $viewModel->description() }}</p>
      </div>

      @if ($viewModel->changeNote() !== null)
        <div>
          <p class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('What changed in this version') }}</p>
          <p class="text-base text-pretty whitespace-pre-line text-body">{{ $viewModel->changeNote() }}</p>
        </div>
      @endif
    </div>
  </x-panel>

  <x-panel :title="__('What people will install')" :subtitle="__('The structure this copies into an account, exactly as it was submitted.')">
    <pre class="overflow-x-auto rounded-md bg-card p-3 font-mono text-xs text-ink">{{ $viewModel->payload() }}</pre>
  </x-panel>

  @if ($viewModel->awaitsDecision())
    <x-panel :title="__('Your decision')">
      <div class="flex flex-col gap-6">
        <x-form :action="$viewModel->approvalUrl()" method="post" class="flex flex-col gap-2">
          <x-button type="submit">{{ __('Let it into the catalogue') }}</x-button>

          <p class="text-sm text-muted">{{ __('The author is told, and anybody can install this version from then on. Nobody holding an earlier version is moved.') }}</p>
        </x-form>

        <x-form :action="$viewModel->rejectionUrl()" method="post" class="flex flex-col gap-3 border-t border-hairline pt-6">
          <x-textarea
            id="reason"
            name="reason"
            :label="__('Why it is being refused')"
            :help="__('The author reads this word for word, so write what they would need to fix.')"
            :error="$errors->first('reason')"
            :rows="4"
            required
          />

          <div>
            <x-button.danger type="submit">{{ __('Refuse this version') }}</x-button.danger>
          </div>
        </x-form>
      </div>
    </x-panel>
  @else
    <x-notice>
      {{ $viewModel->decidedLine() }}
    </x-notice>
  @endif
</x-admin-layout>
