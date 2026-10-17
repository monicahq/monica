{{-- One link: its address, what it shows, and every time it was opened. --}}
{{-- @var \App\ViewModels\Vaults\ShareLinkViewModel $viewModel --}}
@php
  $url = session('shareLinkUrl');
  $visits = $viewModel->visits();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => $viewModel->peopleUrl()],
        ['label' => $viewModel->contact()->name, 'url' => $viewModel->contactUrl()],
        ['label' => __('Sharing links'), 'url' => $viewModel->linksUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
    <div class="flex min-w-0 flex-col gap-6">
      <x-page-header :title="$viewModel->heading()" :description="$viewModel->intro()" class="max-w-prose" />

      @if ($url)
        <div class="flex flex-col gap-3.5 rounded-lg border border-hairline bg-canvas p-5">
          <x-code-value>{{ $url }}</x-code-value>

          <div class="flex flex-wrap items-center gap-2.5">
            <x-copy-button
              :text="$url"
              :label="__('Copy the link')"
              :failure="__('We could not copy it. Select the text and copy it by hand.')"
            />

            <x-button.secondary :href="$url" target="_blank" rel="noopener" size="small">{{ __('Open it as a visitor') }}</x-button.secondary>
          </div>
        </div>
      @else
        <x-notice tone="warning" :title="__('The address is not here')">
          {{ $viewModel->lostLine() }}
        </x-notice>
      @endif

      <x-panel :title="__('What this link shows')" :flush="true">
        <x-slot:action>
          @if ($viewModel->isLive())
            <x-button.secondary :href="$viewModel->editUrl()" data-turbo="true" size="small">{{ __('Change') }}</x-button.secondary>
          @endif
        </x-slot:action>

        <x-detail-list label-width="minmax(0,140px)" label-style="caption">
          @foreach ($viewModel->recap() as $row)
            <x-detail-list.row :label="$row['label']" value-class="text-sm text-pretty text-ink">{{ $row['value'] }}</x-detail-list.row>
          @endforeach
        </x-detail-list>
      </x-panel>

      <x-panel :title="$viewModel->visitsHeader()" icon="timeline" :flush="true">
        @forelse ($visits as $visit)
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-hairline px-4.5 py-3 last:border-b-0">
            <span class="w-45 shrink-0 text-sm text-ink">{{ $visit['when'] }}</span>

            <span class="min-w-0 flex-1 text-sm text-muted">{{ $visit['where'] }}</span>

            @if ($visit['failed'])
              <x-badge tone="danger">{{ __('Wrong password') }}</x-badge>
            @endif

            <span class="min-w-0 max-w-70 truncate font-mono text-2xs text-placeholder">{{ $visit['agent'] }}</span>
          </div>
        @empty
          <p class="px-4.5 py-4 text-sm text-pretty text-muted">{{ $viewModel->visitsEmptyLine() }}</p>
        @endforelse

        <x-slot:footer>
          <x-pagination :paginator="$visits" :size="$viewModel->size()" :default="$viewModel->defaultSize()" id="visits" />
        </x-slot:footer>
      </x-panel>
    </div>

    <div class="flex flex-col gap-4">
      <x-panel :title="__('Sending the link')">
        <p class="text-sm text-pretty text-ink-soft">
          {{ __('Nothing is emailed. Paste the address into whatever you already use to talk to that person. If you set a password, tell them separately from the address.') }}
        </p>
      </x-panel>

      <x-panel :title="__('Anybody holding it can open it')">
        <p class="text-sm text-pretty text-ink-soft">
          {{ __('There is no account behind the address, so the address itself gives access. Treat it as a private document.') }}
        </p>
      </x-panel>

      <x-panel :title="__('Ending it')" variant="inset">
        <p class="text-sm text-pretty text-ink-soft">
          {{ __('Revoking takes effect as soon as you press it, and cannot be undone. If the same person needs access again, make a new link.') }}
        </p>

        @if ($viewModel->isLive())
          <div class="mt-3.5">
            <x-modal.warning
              :title="__('Revoke “:label”?', ['label' => $viewModel->title()])"
              :confirm="__('Revoke the link')"
              :cancel="__('Keep it working')"
              :action="$viewModel->revokeUrl()"
            >
              <x-slot:trigger>
                <x-button.secondary size="small" type="button" @click="open = true">{{ __('Revoke this link') }}</x-button.secondary>
              </x-slot:trigger>

              {{ __('The address stops working straight away. This page and its visits stay, marked revoked. A revoked link never works again.') }}
            </x-modal.warning>
          </div>
        @endif
      </x-panel>

      <x-link :href="$viewModel->linksUrl()" turbo class="text-sm">{{ __('All links for this person') }}</x-link>
    </div>
  </div>
</x-shell>
