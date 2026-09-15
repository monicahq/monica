{{-- Every template this record has rendered from, and when each was applied. --}}
{{-- @var \App\ViewModels\Vaults\ContactViewModel $viewModel --}}
@php
  $current = $viewModel->currentVersion();
  $setAside = $viewModel->setAsideCount();
@endphp

<div class="flex flex-wrap items-start gap-x-6 gap-y-4">
  <p class="max-w-[64ch] text-sm text-pretty text-muted">
    {{ __('Every template this contact has been on, and when it changed. Nothing here was automatic. Each line is a version somebody applied.') }}
  </p>

  <dl class="ml-auto flex flex-wrap gap-x-8 gap-y-3">
    @foreach ($viewModel->versionStats() as $stat)
      <div class="flex flex-col gap-0.5">
        <dt class="text-xs tracking-caption text-muted uppercase">{{ $stat['label'] }}</dt>

        <dd class="text-section-title text-ink">{{ $stat['value'] }}</dd>
      </div>
    @endforeach
  </dl>
</div>

<div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
  <ol class="flex list-none flex-col">
    @foreach ($viewModel->versionHistory() as $entry)
      <li class="grid grid-cols-[92px_36px_minmax(0,1fr)] gap-x-3.5 max-[560px]:grid-cols-[28px_minmax(0,1fr)]">
        <div class="flex flex-col gap-0.5 pt-4 text-right max-[560px]:hidden">
          <span class="text-sm font-semibold text-ink">{{ $entry['date'] }}</span>

          <span class="text-xs text-muted">{{ $entry['ago'] }}</span>
        </div>

        <div class="flex flex-col items-center">
          <span
            @class([
              'mt-3.5 flex size-8 shrink-0 items-center justify-center rounded-sm border ',
              $entry['isCurrent'] ? 'border-ink bg-ink text-white' : 'border-hairline bg-canvas text-muted',
            ])
            aria-hidden="true"
          >
            <x-icon name="timeline" :size="16" :stroke-width="1.7" />
          </span>

          @unless ($loop->last)
            <span class="min-h-6 w-0.5 flex-1 bg-hairline"></span>
          @endunless
        </div>

        <div
          @class([
            'mt-3.5 mb-5 flex flex-col gap-2.5 rounded-lg border px-4.5 py-4',
            $entry['isCurrent'] ? 'border-hairline-strong border-b-2 bg-canvas ' : 'border-hairline bg-canvas',
          ])
        >
          <div class="flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
            <h3 class="text-card-title text-ink">{{ $entry['title'] }}</h3>

            @if ($entry['badge'])
              <x-badge :tone="$entry['isCurrent'] ? 'accent' : 'neutral'">{{ $entry['badge'] }}</x-badge>
            @endif

            <span class="ml-auto text-xs text-muted">{{ $entry['who'] }}</span>
          </div>

          <p class="text-xs text-muted min-[560px]:hidden">{{ $entry['date'] }}</p>

          @if ($entry['body'])
            <p class="max-w-[62ch] text-sm text-pretty text-body">{{ $entry['body'] }}</p>
          @endif

          <div class="flex flex-wrap items-center gap-3 border-t border-hairline pt-3">
            <span class="text-xs text-muted">{{ $entry['span'] }}</span>

            @if ($entry['goBack'])
              <x-form method="put" :action="$entry['goBack']['url']" class="ml-auto">
                <input type="hidden" name="structure_version_id" value="{{ $entry['goBack']['versionId'] }}" />

                <x-button.secondary size="small">{{ $entry['goBack']['label'] }}</x-button.secondary>
              </x-form>
            @elseif ($entry['note'])
              <span class="ml-auto max-w-[40ch] text-xs text-pretty text-placeholder">{{ $entry['note'] }}</span>
            @endif
          </div>
        </div>
      </li>
    @endforeach
  </ol>

  <div class="flex flex-col gap-4">
    <x-panel :title="__('On right now')" icon="file" :flush="true">
      <dl class="grid gap-0">
        <div class="grid grid-cols-[100px_minmax(0,1fr)] items-baseline gap-3 border-b border-hairline px-4.5 py-3">
          <dt class="text-sm text-muted">{{ __('Template') }}</dt>

          <dd class="text-base font-semibold text-ink">{{ $current['template'] }}</dd>
        </div>

        <div class="grid grid-cols-[100px_minmax(0,1fr)] items-baseline gap-3 border-b border-hairline px-4.5 py-3">
          <dt class="text-sm text-muted">{{ __('Version') }}</dt>

          <dd class="flex flex-wrap items-baseline gap-2">
            <span class="font-mono text-[13px] text-ink">v{{ $current['number'] }}</span>

            <span class="text-xs text-muted">{{ __('since :date', ['date' => $current['since']]) }}</span>
          </dd>
        </div>

        <div class="grid grid-cols-[100px_minmax(0,1fr)] items-baseline gap-3 px-4.5 py-3">
          <dt class="text-sm text-muted">{{ __('Shows') }}</dt>

          <dd class="text-base text-ink">{{ $current['shows'] }}</dd>
        </div>
      </dl>
    </x-panel>

    @if ($update = $viewModel->templateUpdate())
      <x-notice tone="info" icon="file">
        {{ __('Version :number exists and this contact is not on it. Applying it is recorded on this history like any other change.', ['number' => $update['number']]) }}

        <x-slot:action>
          <x-form method="put" :action="$update['applyUrl']">
            <x-button size="small">{{ $update['label'] }}</x-button>
          </x-form>
        </x-slot:action>
      </x-notice>
    @endif

    <x-notice tone="warning">
      {{ $viewModel->setAsideLine() }}

      @if ($setAside > 0)
        <x-slot:action>
          <x-button.secondary :href="$viewModel->setAsideUrl()" data-turbo="true" size="small">
            {{ __('Unused answers') }}
          </x-button.secondary>
        </x-slot:action>
      @endif
    </x-notice>
  </div>
</div>
