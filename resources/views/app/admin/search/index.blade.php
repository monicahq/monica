{{-- Whether the search engine answers, and what each index holds beside what the instance holds. --}}
{{-- @var \App\ViewModels\Admin\SearchEngineViewModel $viewModel --}}
<x-admin-layout :title="__('Search engine')">
  <x-modal.confirm-password
    :offers-password="$viewModel->credentials()->offersPassword()"
    :offers-passkey="$viewModel->credentials()->offersPasskey()"
    :description="__('What you are about to change decides how this instance behaves for everybody, so we ask for a credential first. You will not be asked again for a little while.')"
  />

  <x-page-header :title="__('Search engine')" :description="__('Which search engine this instance uses, whether it answers, and what each index holds beside what the instance holds.')">
    <x-slot:actions>
      <x-button.secondary :href="route('admin.search.index')">{{ __('Check again') }}</x-button.secondary>
    </x-slot:actions>
  </x-page-header>

  <div class="flex flex-col gap-6">
    <x-panel :title="__('The engine')" icon="search" :flush="true">
      <x-detail-list layout="stacked" label-style="caption">
        <x-detail-list.row :label="__('Search engine')">{{ $viewModel->engineName() }}</x-detail-list.row>

        <x-detail-list.row :label="__('Health')">
          <x-badge :tone="$viewModel->healthTone()">{{ $viewModel->healthLabel() }}</x-badge>

          @if ($viewModel->healthNote() !== null)
            <span class="mt-1 block text-sm break-words text-body">{{ $viewModel->healthNote() }}</span>
          @endif
        </x-detail-list.row>

        @if ($viewModel->responseTime() !== null)
          <x-detail-list.row :label="__('Answered in')">{{ $viewModel->responseTime() }}</x-detail-list.row>
        @endif

        @if ($viewModel->address() !== null)
          <x-detail-list.row :label="__('Address')" value-class="font-mono text-sm break-all text-ink">{{ $viewModel->address() }}</x-detail-list.row>
        @endif

        @if ($viewModel->version() !== null)
          <x-detail-list.row :label="__('Version')">{{ $viewModel->version() }}</x-detail-list.row>
        @endif

        @if ($viewModel->totalSize() !== null)
          <x-detail-list.row :label="__('Total index size')">{{ $viewModel->totalSize() }}</x-detail-list.row>
        @endif

        @if ($viewModel->typingMistakesNote() !== null)
          <x-detail-list.row :label="__('Typing mistakes')">{{ $viewModel->typingMistakesNote() }}</x-detail-list.row>
        @endif
      </x-detail-list>
    </x-panel>

    <x-panel :title="__('The indexes')" title-id="search-indexes-title" icon="layers" :flush="true">
      <x-scroll-region labelledby="search-indexes-title">
        <table class="w-full text-sm">
          <caption class="sr-only">{{ __('What each index holds, beside what the instance holds') }}</caption>
          <thead>
            <tr class="border-b border-hairline text-left text-xs text-muted">
              <th scope="col" class="px-4 py-2.5 font-medium">{{ __('Index') }}</th>
              <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Documents indexed') }}</th>
              <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Records') }}</th>
              <th scope="col" class="px-4 py-2.5 font-medium">{{ __('Difference') }}</th>
              <th scope="col" class="px-4 py-2.5 text-right font-medium">{{ __('Size') }}</th>
              @if ($viewModel->canRebuild())
                <th scope="col" class="px-4 py-2.5 font-medium"><span class="sr-only">{{ __('Rebuild') }}</span></th>
              @endif
            </tr>
          </thead>
          <tbody>
            @foreach ($viewModel->indexes() as $index)
              <tr class="border-b border-hairline last:border-b-0">
                <th scope="row" class="px-4 py-2.5 text-left font-normal text-ink">
                  {{ $index['label'] }}
                  <span class="block font-mono text-xs break-all text-muted">{{ $index['name'] }}</span>
                </th>
                <td class="px-4 py-2.5 text-right text-ink">{{ $index['documents'] }}</td>
                <td class="px-4 py-2.5 text-right text-ink">{{ $index['records'] }}</td>
                <td class="px-4 py-2.5 text-ink">{{ $index['difference'] }}</td>
                <td class="px-4 py-2.5 text-right text-ink">{{ $index['size'] }}</td>
                @if ($viewModel->canRebuild())
                  <td class="px-4 py-2.5 text-right">
                    <x-modal.warning :title="__('Rebuild the index of :kind?', ['kind' => $index['label']])" :confirm="__('Rebuild it')" method="post" :action="$index['rebuildUrl']" confirms-password>
                      <x-slot:trigger>
                        <x-button.secondary type="button" size="small" @click="open = true">{{ __('Rebuild this index') }}<span class="sr-only"> {{ $index['label'] }}</span></x-button.secondary>
                      </x-slot:trigger>

                      {{ __('The index is emptied, then every record is sent again. Until that has finished, search misses records of this kind for every account.') }}
                    </x-modal.warning>
                  </td>
                @endif
              </tr>
            @endforeach
          </tbody>
        </table>
      </x-scroll-region>
    </x-panel>

    <x-panel :title="__('How changes reach the engine')" icon="settings" :flush="true">
      <x-detail-list layout="stacked" label-style="caption">
        <x-detail-list.row :label="__('Index prefix')">{{ $viewModel->prefix() }}</x-detail-list.row>
        <x-detail-list.row :label="__('Changes')">{{ $viewModel->queueLabel() }}</x-detail-list.row>

        @if ($viewModel->indexingLabel() !== null)
          <x-detail-list.row :label="__('Still indexing')">{{ $viewModel->indexingLabel() }}</x-detail-list.row>
        @endif
      </x-detail-list>
    </x-panel>
  </div>
</x-admin-layout>
