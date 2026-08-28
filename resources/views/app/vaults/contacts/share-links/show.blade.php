{{--
  One link: its address, what it shows, and every time it was opened.

  The address is on the screen exactly once, in the response to the request that
  minted it, because only a hash of the token is written down. Coming back here
  later is coming back to everything except that, and the panel says so rather
  than looking like something failed to load. A link nobody copied is replaced, not
  recovered.

  The visits are what makes the feature feel safe enough to use, and what they hold
  is deliberately coarse. There is no account behind a visitor, so the list answers
  "was it opened, roughly when, roughly from where" and stops. A failed password is
  in the list too: a run of those is the only signal the owner ever gets that the
  address may have gone somewhere they did not send it.

  @var \App\ViewModels\Vaults\ShareLinkViewModel $viewModel
--}}
@php
  $url = session('shareLinkUrl');
  $visits = $viewModel->visits();
@endphp
<x-shell :title="$viewModel->title()" wide>
  <x-slot:breadcrumb>
    <x-breadcrumb :items="[['label' => $viewModel->vault()->name, 'url' => route('vaults.show', $viewModel->vault())], ['label' => __('People'), 'url' => $viewModel->peopleUrl()],
        ['label' => $viewModel->contact()->name, 'url' => $viewModel->contactUrl()],
        ['label' => __('Links'), 'url' => $viewModel->linksUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
    <div class="flex min-w-0 flex-col gap-6">
      <header class="flex max-w-[66ch] flex-col gap-2">
        <h1 class="font-display text-page-title text-balance text-ink">{{ $viewModel->heading() }}</h1>

        <p class="text-base text-pretty text-muted">{{ $viewModel->intro() }}</p>
      </header>

      @if ($url)
        <div class="flex flex-col gap-3.5 rounded-lg border border-hairline bg-canvas p-5 " x-data="{ copied: false }">
          <p
            x-ref="url"
            class="rounded-md border border-hairline bg-sunken px-3.5 py-3 font-mono text-[12.5px] break-all text-ink select-all"
          >{{ $url }}</p>

          <div class="flex flex-wrap items-center gap-2.5">
            <x-button
              type="button"
              size="small"
              @click="navigator.clipboard.writeText($refs.url.textContent).then(() => { copied = true; setTimeout(() => copied = false, 2500) })"
            >
              <span x-show="! copied">{{ __('Copy the link') }}</span>

              <span x-cloak x-show="copied">{{ __('Copied') }}</span>
            </x-button>

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

        <dl class="grid gap-0">
          @foreach ($viewModel->recap() as $row)
            <div class="grid grid-cols-[minmax(0,140px)_minmax(0,1fr)] items-baseline gap-4 border-b border-hairline px-4.5 py-3 last:border-b-0 max-[560px]:grid-cols-1 max-[560px]:gap-0.5">
              <dt class="text-xs tracking-(--tracking-caption) text-muted uppercase">{{ $row['label'] }}</dt>

              <dd class="text-sm text-pretty text-ink">{{ $row['value'] }}</dd>
            </div>
          @endforeach
        </dl>
      </x-panel>

      <x-panel :title="$viewModel->visitsHeader()" icon="timeline" :flush="true">
        @forelse ($visits as $visit)
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-hairline px-4.5 py-3 last:border-b-0">
            <span class="w-45 shrink-0 text-sm text-ink">{{ $visit['when'] }}</span>

            <span class="min-w-0 flex-1 text-sm text-muted">{{ $visit['where'] }}</span>

            @if ($visit['failed'])
              <x-badge tone="danger">{{ __('Wrong password') }}</x-badge>
            @endif

            <span class="min-w-0 max-w-70 truncate font-mono text-[11.5px] text-placeholder">{{ $visit['agent'] }}</span>
          </div>
        @empty
          <p class="px-4.5 py-4 text-sm text-pretty text-muted">{{ $viewModel->visitsEmptyLine() }}</p>
        @endforelse
      </x-panel>
    </div>

    <div class="flex flex-col gap-4">
      <x-panel :title="__('Sending it on')">
        <p class="text-sm text-pretty text-ink-soft">
          {{ __('Nothing is emailed. You paste the address into whatever you already use to talk to that person, and if you set a password you tell them separately from the address.') }}
        </p>
      </x-panel>

      <x-panel :title="__('Anybody holding it can open it')">
        <p class="text-sm text-pretty text-ink-soft">
          {{ __('There is no account behind the address, so it is the address itself that grants the reading. Send it the way you would send a private document.') }}
        </p>
      </x-panel>

      <x-panel :title="__('Ending it')" variant="inset">
        <p class="text-sm text-pretty text-ink-soft">
          {{ __('Revoking works the moment you press it and cannot be undone. If the same person needs access again, you make a new link.') }}
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

              {{ __('The address stops working straight away. This page and its visits stay, marked revoked, and a revoked link is never brought back.') }}
            </x-modal.warning>
          </div>
        @endif
      </x-panel>

      <x-link :href="$viewModel->linksUrl()" turbo class="text-sm">{{ __('All links for this person') }}</x-link>
    </div>
  </div>
</x-shell>
