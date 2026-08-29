{{--
  One submission, with everything its author sent in.

  The payload is on the screen in full rather than summarised. It is the structure
  that will be copied into other people's accounts once this is approved, and a
  decision taken on a name and a description is not a decision.

  Approving is one click because approving is the ordinary case. Refusing takes a
  reason, which is not a courtesy: the author is the one who has to act on it, and
  a refusal they cannot read is a refusal they cannot answer.

  A submission somebody else has already decided shows what stands instead of
  controls that would fail.

  @var \App\ViewModels\Admin\ModerationSubmissionViewModel $viewModel
--}}
<x-admin-layout :title="$viewModel->listing()->name">
  <header class="flex flex-col gap-2">
    <x-link :href="route('admin.moderation.index')" data-turbo="true">{{ __('Back to moderation') }}</x-link>

    <h1 class="text-page-title text-ink">{{ $viewModel->listing()->name }}</h1>

    <p class="text-sm text-muted">
      {{ $viewModel->listing()->author_name }}
      &middot; {{ $viewModel->listing()->category->name }}
      &middot; {{ __('version :version', ['version' => $viewModel->version()->version_number]) }}
      &middot; {{ __('submitted :date', ['date' => $viewModel->version()->created_at->isoFormat('LL')]) }}
    </p>
  </header>

  @error('decision')
    <x-notice tone="danger">{{ $message }}</x-notice>
  @enderror

  <x-panel :title="__('What the author says')">
    <div class="flex flex-col gap-4">
      <div>
        <p class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Summary') }}</p>
        <p class="text-base text-pretty text-body">{{ $viewModel->listing()->summary }}</p>
      </div>

      <div>
        <p class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('Description') }}</p>
        <p class="text-base text-pretty whitespace-pre-line text-body">{{ $viewModel->listing()->description }}</p>
      </div>

      @if ($viewModel->version()->change_note !== null)
        <div>
          <p class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ __('What changed in this version') }}</p>
          <p class="text-base text-pretty whitespace-pre-line text-body">{{ $viewModel->version()->change_note }}</p>
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
        <x-form :action="route('admin.moderation.approval.create', ['version' => $viewModel->version()->id])" method="post" class="flex flex-col gap-2">
          <x-button type="submit">{{ __('Let it into the catalogue') }}</x-button>

          <p class="text-sm text-muted">{{ __('The author is told, and anybody can install this version from then on. Nobody holding an earlier version is moved.') }}</p>
        </x-form>

        <x-form :action="route('admin.moderation.rejection.create', ['version' => $viewModel->version()->id])" method="post" class="flex flex-col gap-3 border-t border-hairline pt-6">
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
      {{ __('This version is :status, so there is nothing to decide. :moderator settled it.', [
        'status' => $viewModel->version()->status->value,
        'moderator' => $viewModel->listing()->moderated_by ?? __('Somebody else'),
      ]) }}
    </x-notice>
  @endif
</x-admin-layout>
