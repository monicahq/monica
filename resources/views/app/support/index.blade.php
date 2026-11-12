{{-- Everything somebody has asked the support team, open or solved. --}}
{{-- @var \App\ViewModels\Support\SupportRequestsViewModel $viewModel --}}
<x-support.page
  :title="__('Your requests')"
  current="requests"
  :open-count="$viewModel->openCount()"
  :crumbs="[['label' => __('Support'), 'url' => route('support.requests.index')], ['label' => __('Your requests')]]"
>
  <x-page-header :title="__('Your requests')" :description="__('Everything you have asked the support team, with the latest activity on top.')">
    @if (\App\Helpers\SupportAvailability::isOffered())
      <x-slot:actions>
        <x-button :href="route('support.requests.new')">{{ __('New request') }}</x-button>
      </x-slot:actions>
    @endif
  </x-page-header>

  <div class="space-y-3">
    <x-tabs :aria-label="__('Requests')">
      <x-tabs.item :href="route('support.requests.index')" :selected="! $viewModel->showsSolved()" :count="$viewModel->openCount()">{{ __('Open') }}</x-tabs.item>
      <x-tabs.item :href="route('support.requests.index', ['view' => 'solved'])" :selected="$viewModel->showsSolved()" :count="$viewModel->solvedCount()">{{ __('Solved') }}</x-tabs.item>
    </x-tabs>

    <x-panel :flush="true">
      @forelse ($viewModel->rows() as $row)
        <x-row :href="$row['url']" :title="$row['subject']" :icon="$row['categoryIcon']" :stack="true">
          <x-slot:description>
            <span class="font-mono">{{ $row['reference'] }}</span> · {{ $row['categoryLabel'] }} · {{ $row['updated'] }}
          </x-slot:description>

          <x-slot:end>
            <x-badge :tone="$row['statusTone']" :icon="$row['statusIcon']">{{ $row['statusLabel'] }}</x-badge>
          </x-slot:end>
        </x-row>
      @empty
        <x-empty-state :title="__('Nothing here yet')">
          <x-slot:icon>
            <x-icon name="mail" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('Solved requests stay here, so you can find an old answer without searching your inbox.') }}
        </x-empty-state>
      @endforelse
    </x-panel>
  </div>
</x-support.page>
