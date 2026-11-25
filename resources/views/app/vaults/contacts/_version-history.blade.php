{{-- Every template version this record has been on, newest first, with the way back to an older one. --}}
{{-- @var \App\ViewModels\Vaults\ContactViewModel $viewModel --}}
<div class="mx-auto flex w-full max-w-205 flex-col gap-6">
  <x-notice tone="info">
    {{ __('A template defines the sections and fields on this contact’s profile. This page shows which versions the contact has used and lets you return to an earlier one. All the information you’ve entered is kept, even if some of it isn’t visible in the version you choose.') }}
  </x-notice>

  <ol class="flex list-none flex-col">
    @foreach ($viewModel->versionHistory() as $entry)
      <li class="grid grid-cols-[132px_32px_minmax(0,1fr)] gap-x-4 max-xs:grid-cols-[32px_minmax(0,1fr)]">
        <div class="flex flex-col items-end gap-0.5 pt-3.5 text-right max-xs:hidden">
          <span class="text-sm font-medium text-ink">{{ $entry['date'] }}</span>

          <span class="text-xs text-muted">{{ $entry['ago'] }}</span>
        </div>

        <div class="flex flex-col items-center" aria-hidden="true">
          <span @class(['h-3 w-px shrink-0 bg-hairline', 'invisible' => $loop->first])></span>

          <span
            @class([
              'flex size-8 shrink-0 items-center justify-center rounded-full border',
              $entry['isCurrent'] ? 'border-ink bg-ink text-card' : 'border-hairline bg-card text-muted',
            ])
          >
            <x-icon name="layers" :size="14" />
          </span>

          <span @class(['w-px flex-1 bg-hairline', 'invisible' => $loop->last])></span>
        </div>

        <div style="padding-bottom: {{ $entry['spaceBelow'] }}px">
          <div
            @class([
              'flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-lg border bg-card px-4 py-3.5',
              $entry['isCurrent'] ? 'border-ink' : 'border-hairline',
            ])
          >
            <div class="flex flex-wrap items-center gap-2">
              <h3 class="text-base font-semibold text-ink">{{ $entry['title'] }}</h3>

              @if ($entry['isCurrent'])
                <x-badge tone="accent">{{ __('Current') }}</x-badge>
              @endif

              <span class="w-full text-xs text-muted xs:hidden">{{ $entry['date'] }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <span class="text-xs text-muted">{{ $entry['span'] }}</span>

              @if ($entry['revert'])
                <x-modal.warning
                  method="put"
                  :action="$entry['revert']['url']"
                  :title="$entry['revert']['title']"
                  :confirm="$entry['revert']['confirm']"
                  :cancel="$entry['revert']['cancel']"
                >
                  <x-slot:trigger>
                    <x-button.secondary type="button" size="small" @click="open = true">{{ __('Revert') }}</x-button.secondary>
                  </x-slot:trigger>

                  <p>{{ $entry['revert']['body'] }}</p>

                  <x-slot:fields>
                    <input type="hidden" name="structure_version_id" value="{{ $entry['revert']['versionId'] }}" />

                    @if ($entry['revert']['template'])
                      <input type="hidden" name="template" value="{{ $entry['revert']['template'] }}" />
                    @endif
                  </x-slot:fields>
                </x-modal.warning>
              @elseif ($entry['refusal'])
                <span class="max-w-reading-narrow text-xs text-pretty text-placeholder">{{ $entry['refusal'] }}</span>
              @endif
            </div>
          </div>
        </div>
      </li>
    @endforeach
  </ol>

  @if ($setAside = $viewModel->setAsideLine())
    <p class="text-sm text-pretty text-muted">
      {{ $setAside }}
      <x-link :href="$viewModel->setAsideUrl()" turbo>{{ __('Unused answers') }}</x-link>
    </p>
  @endif
</div>
