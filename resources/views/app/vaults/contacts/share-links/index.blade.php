{{-- Every link ever opened onto one person's page. --}}
{{-- @var \App\ViewModels\Vaults\ContactShareLinksViewModel $viewModel --}}
@php
  $links = $viewModel->links();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => $viewModel->peopleUrl()],
        ['label' => $viewModel->contact()->name, 'url' => $viewModel->contactUrl()],
        ['label' => __('Sharing links')],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <div class="flex flex-wrap items-end gap-4">
    <div class="min-w-60 flex-1">
      <x-page-header :title="$viewModel->title()" :description="$viewModel->intro()" />
    </div>

    <x-button :href="$viewModel->newUrl()" data-turbo="true" size="small" class="shrink-0">
      <x-icon name="plus" :size="15" :stroke-width="1.8" />
      {{ __('Create sharing link') }}
    </x-button>
  </div>

  <div id="share-links" x-merge="replace" class="dim-when-busy">
    <x-panel :flush="true">
      @forelse ($links as $link)
        <x-row icon="link">
          <div class="flex flex-wrap items-center gap-2.5">
            <a
              href="{{ $link['showUrl'] }}"
              data-turbo="true"
              @class([
                'text-base font-semibold no-underline transition-colors duration-(--duration-fast) ease-out hover:text-link',
                'text-ink' => $link['isLive'],
                'text-muted' => ! $link['isLive'],
              ])
            >{{ $link['label'] }}</a>

            <x-badge :tone="$link['stateTone']">{{ $link['state'] }}</x-badge>
          </div>

          <p class="mt-px text-sm text-muted">{{ $link['meta'] }}</p>

          <p class="mt-1 text-sm text-pretty text-ink-soft">{{ $link['shows'] }}</p>

          <p class="mt-0.5 text-xs text-muted">
            {{ $link['opened'] }} ⋅ {{ $link['lastSeen'] }} ⋅ {{ $link['ends'] }}
          </p>

          <x-slot:end>
            @if ($link['isLive'])
              <x-modal.warning
                :title="__('Revoke “:label”?', ['label' => $link['label']])"
                :confirm="__('Revoke the link')"
                :cancel="__('Keep it working')"
                :action="$link['revokeUrl']"
                x-target="share-links"
              >
                <x-slot:trigger>
                  <x-button.secondary size="small" type="button" @click="open = true">{{ __('Revoke') }}</x-button.secondary>
                </x-slot:trigger>

                {{ $link['revokeLine'] }}
              </x-modal.warning>
            @endif
          </x-slot:end>
        </x-row>
      @empty
        <x-empty-state :title="__('Create your first sharing link')">
          <x-slot:icon>
            <x-icon name="link" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ $viewModel->emptyLine() }}

          <x-slot:action>
            <x-button :href="$viewModel->newUrl()" data-turbo="true" size="small">{{ __('Create sharing link') }}</x-button>
          </x-slot:action>
        </x-empty-state>
      @endforelse
    </x-panel>
  </div>

  <x-notice tone="quiet" icon="lock" :title="__('Why revoked links stay on the list')">
    {{ __('A revoked link is still part of the history of who saw this page. Removing the row would remove that history. A revoked link never works again.') }}
  </x-notice>
</x-shell>
