{{-- One agent feedback report: what went wrong, what the agent tried, the fix it suggests, and notes. --}}
{{-- @var \App\ViewModels\Admin\AgentFeedbackReportViewModel $viewModel --}}
@php
  $report = $viewModel->report();
  $person = $viewModel->person();
  $sameTool = $viewModel->sameTool();
  $steps = $viewModel->steps();
@endphp

<x-admin-layout :title="$report['title']">
  <x-breadcrumb :items="[
    ['label' => __('Agent reports'), 'url' => route('admin.agentFeedback.index')],
    ['label' => $report['number']],
  ]" />

  <div class="flex flex-wrap items-start gap-8">
    <article aria-labelledby="report-title" class="flex max-w-205 min-w-0 flex-[999_1_480px] flex-col gap-8">
      <header class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center gap-1.5">
          <x-badge :tone="$report['statusTone']">{{ $report['statusLabel'] }}</x-badge>
          <x-badge :tone="$report['severityTone']">{{ $report['severityLabel'] }}</x-badge>
          <x-badge tone="info">{{ $report['kindLabel'] }}</x-badge>
        </div>
        <h1 id="report-title" class="text-page-title text-pretty text-ink">{{ $report['title'] }}</h1>
        <p class="text-sm text-body">{{ $report['byline'] }}</p>

        <div class="flex flex-wrap items-center gap-2 pt-1">
          @foreach ($viewModel->transitions() as $transition)
            <x-form method="put" :action="route('admin.agentFeedback.status.update', ['agentFeedbackReport' => $report['uuid']])">
              <input type="hidden" name="status" value="{{ $transition['value'] }}" />
              <x-button size="small" :variant="$transition['primary'] ? 'primary' : 'secondary'">{{ $transition['label'] }}</x-button>
            </x-form>
          @endforeach

          <x-copy-button
            :text="$viewModel->markdown()"
            :label="__('Copy as Markdown')"
            :failure="__('We could not copy it. Select the text and copy it by hand.')"
            variant="ghost"
          />
        </div>

        @error('status')
          <p class="text-sm text-error" role="alert">{{ $message }}</p>
        @enderror
      </header>

      @if ($report['request'] !== null)
        <section aria-labelledby="request-title" class="flex flex-col gap-2.5">
          <h2 id="request-title" class="text-base font-semibold text-ink">{{ __('What the person asked') }}</h2>
          <blockquote class="rounded-lg border border-hairline bg-card px-4 py-3.5 text-base text-pretty whitespace-pre-line text-ink-soft">{{ $report['request'] }}</blockquote>
          <p class="text-xs text-pretty text-muted">{{ __('Paraphrased by the agent, without names or details from the vault.') }}</p>
        </section>
      @endif

      @if ($steps !== [])
        <section aria-labelledby="steps-title" class="flex flex-col gap-2.5">
          <h2 id="steps-title" class="text-base font-semibold text-ink">{{ __('What the agent tried') }}</h2>
          <ol class="overflow-hidden rounded-lg border border-hairline bg-canvas shadow-panel">
            @foreach ($steps as $step)
              <li class="grid grid-cols-[28px_minmax(0,1fr)] gap-x-2 gap-y-1 border-b border-hairline-soft px-4 py-3 last:border-b-0">
                <span class="pt-0.5 text-xs text-muted tabular-nums">{{ $step['number'] }}</span>
                <div class="flex min-w-0 flex-col gap-1.5">
                  <div class="flex flex-wrap items-center gap-2">
                    <code class="font-mono text-xs font-semibold text-ink">{{ $step['tool'] }}</code>
                    <x-badge :tone="$step['succeeded'] ? 'neutral' : 'danger'">{{ $step['succeeded'] ? __('Success') : __('Error') }}</x-badge>
                  </div>
                  @if ($step['arguments'] !== null)
                    <code class="block font-mono text-xs leading-normal wrap-anywhere whitespace-pre-wrap text-body">{{ $step['arguments'] }}</code>
                  @endif
                  @if ($step['result'] !== null)
                    <p class="text-sm leading-normal text-pretty text-body">{{ $step['result'] }}</p>
                  @endif
                </div>
              </li>
            @endforeach
          </ol>
        </section>
      @endif

      <section aria-labelledby="wrong-title" class="flex flex-col gap-3">
        <h2 id="wrong-title" class="text-base font-semibold text-ink">{{ __('What went wrong') }}</h2>
        <dl class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-x-6 gap-y-4">
          <div class="flex flex-col gap-1">
            <dt class="text-xs font-medium text-muted">{{ __('Observed') }}</dt>
            <dd class="text-base text-pretty whitespace-pre-line text-body">{{ $report['observed'] }}</dd>
          </div>
          <div class="flex flex-col gap-1">
            <dt class="text-xs font-medium text-muted">{{ __('Expected') }}</dt>
            <dd class="text-base text-pretty whitespace-pre-line text-body">{{ $report['expected'] }}</dd>
          </div>
        </dl>
      </section>

      <section aria-labelledby="fix-title" class="flex flex-col gap-2.5">
        <h2 id="fix-title" class="text-base font-semibold text-ink">{{ __('Proposed fix') }}</h2>
        <p class="max-w-[72ch] text-base text-pretty whitespace-pre-line text-body">{{ $report['proposedFix'] }}</p>
        @if ($report['proposedCode'] !== null)
          <pre tabindex="0" role="region" aria-labelledby="fix-title" class="overflow-x-auto rounded-lg focus-ring-inset border border-hairline bg-card px-4 py-3.5 font-mono text-xs leading-relaxed text-ink-soft"><code>{{ $report['proposedCode'] }}</code></pre>
        @endif
        <p class="text-xs text-muted">{{ __('Suggested by the agent. Nobody has checked it yet.') }}</p>
      </section>

      <section aria-labelledby="notes-title" class="flex flex-col gap-2.5">
        <h2 id="notes-title" class="text-base font-semibold text-ink">{{ __('Notes') }}</h2>
        @php
          $notes = $viewModel->notes();
        @endphp

        @if ($notes !== [])
          <ol class="flex flex-col gap-2.5">
            @foreach ($notes as $note)
              <li class="flex flex-col gap-1.5 rounded-lg border border-hairline px-4 py-3">
                <div class="flex items-center gap-2">
                  <x-avatar-initials :name="$note['author']" :size="22" />
                  <span class="text-sm font-semibold text-ink">{{ $note['author'] }}</span>
                  <span class="ml-auto text-xs text-muted">{{ $note['when'] }}</span>
                </div>
                <p class="text-base text-pretty whitespace-pre-line text-body">{{ $note['body'] }}</p>
              </li>
            @endforeach
          </ol>
        @endif

        <x-form method="post" :action="route('admin.agentFeedback.notes.create', ['agentFeedbackReport' => $report['uuid']])" class="flex flex-col items-start gap-3">
          <div class="w-full">
            <x-textarea
              id="note-body"
              name="body"
              :label="__('Add a note for other administrators')"
              :rows="2"
              :value="old('body')"
              :error="$errors->get('body')"
              required
            />
          </div>
          <x-button.secondary size="small">{{ __('Add note') }}</x-button.secondary>
        </x-form>
      </section>
    </article>

    <section class="flex max-w-full min-w-0 flex-[1_0_280px] flex-col gap-4" aria-label="{{ __('About this report') }}">
      <x-panel :title="__('On behalf of')" :flush="true">
        <div class="flex items-center gap-2.5 border-b border-hairline px-4 py-3.5">
          <x-avatar-initials :name="$person['name']" :size="32" />
          <div class="flex min-w-0 flex-col">
            <span class="text-sm font-semibold text-ink">{{ $person['name'] }}</span>
            <span class="text-xs wrap-anywhere text-muted">{{ $person['email'] }}</span>
          </div>
        </div>
        <div class="px-4 py-2.5">
          <x-link :href="$person['accountUrl']" class="text-xs">{{ __('Open this account') }}</x-link>
        </div>
      </x-panel>

      <x-panel :title="__('Session')" :flush="true">
        <dl class="grid grid-cols-[auto_minmax(0,1fr)]">
          @foreach ($viewModel->session() as $fact)
            <dt class="border-b border-hairline-soft py-2.25 pr-3 pl-4 text-xs text-muted last-of-type:border-b-0">{{ $fact['label'] }}</dt>
            <dd class="border-b border-hairline-soft py-2.25 pr-4 text-right text-xs wrap-anywhere text-ink last-of-type:border-b-0">{{ $fact['value'] }}</dd>
          @endforeach
        </dl>
      </x-panel>

      @if ($sameTool !== [])
        <x-panel :title="__('Same tool')" :flush="true">
          <ul>
            @foreach ($sameTool as $other)
              <li class="border-b border-hairline-soft last:border-b-0">
                <a href="{{ $other['url'] }}" data-turbo="true" class="flex flex-col gap-0.5 px-4 py-2.5 no-underline hover:bg-hover focus-ring-inset">
                  <span class="text-sm font-medium text-pretty text-ink">{{ $other['title'] }}</span>
                  <span class="text-xs text-muted">{{ $other['meta'] }}</span>
                </a>
              </li>
            @endforeach
          </ul>
        </x-panel>
      @endif
    </section>
  </div>
</x-admin-layout>
