{{-- Choosing which other contact of the vault is the same person as this one. --}}
{{-- @var \App\ViewModels\Vaults\ContactMergeViewModel $viewModel --}}
@php
  $candidates = $viewModel->candidates();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => route('contacts.index', $viewModel->vault())],
        ['label' => $viewModel->contact()->name, 'url' => $viewModel->backUrl()],
        ['label' => __('Merge')],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->intro()" />

  <x-error :messages="$errors->get('other')" />

  <form method="get" action="{{ $viewModel->searchUrl() }}" role="search" class="w-72 max-w-full">
    <input type="hidden" name="size" value="{{ $viewModel->size() }}" />

    <x-input
      type="search"
      id="query"
      size="small"
      :value="$viewModel->query()"
      :placeholder="__('Search names and nicknames')"
      :aria-label="__('Search the people in this vault')"
      class="w-full"
    />
  </form>

  <x-list-panel
    :items="$candidates"
    :paginator="$candidates"
    :size="$viewModel->size()"
    :default="$viewModel->defaultSize()"
    :title="__('Who is the same person?')"
    icon="contact"
  >
    <x-slot:empty>
      <x-empty-state :title="__('Nobody to merge with')" :description="$viewModel->emptyLine()">
        <x-slot:icon>
          <x-icon name="contact" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        <x-slot:action>
          <x-button.secondary :href="$viewModel->backUrl()" data-turbo="true" size="small">
            {{ __('Back to the record') }}
          </x-button.secondary>
        </x-slot:action>
      </x-empty-state>
    </x-slot:empty>

    <ul class="grid gap-0">
      @foreach ($candidates as $candidate)
        <li class="flex items-center justify-between gap-4 border-b border-hairline px-4.5 py-3 last:border-b-0">
          <span class="text-sm font-semibold text-ink">{{ $candidate['name'] }}</span>

          <x-button.secondary :href="$candidate['url']" data-turbo="true" size="small">
            {{ __('Compare them') }}
          </x-button.secondary>
        </li>
      @endforeach
    </ul>
  </x-list-panel>
</x-shell>
