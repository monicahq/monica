{{-- Where the address of a contact a merge absorbed lands: who they became, and the way through. --}}
{{-- @var \App\ViewModels\Vaults\AbsorbedContactViewModel $viewModel --}}
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb.vault :vault="$viewModel->vault()" :items="[['label' => __('People'), 'url' => $viewModel->peopleUrl()],
        ['label' => $viewModel->title()],]" />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="contacts" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->explanation()" />

  <x-panel :title="__('Where they are now')" icon="contact">
    <x-button :href="$viewModel->keptUrl()" variant="primary">
      {{ __('Open :name', ['name' => $viewModel->keptName()]) }}
    </x-button>
  </x-panel>
</x-shell>
