{{--
  What this vault has already told somebody about.

  It is the answer to "did that reminder actually go out", which is the question
  the list beside it cannot answer: that one is about what is coming. A row here
  exists because a deadline was notified, so a row survives the deletion of the
  rule that produced it, and a test send, which never sets that mark, cannot
  appear here at all.

  The history has no horizon, unlike the ninety days next door, so it is paged
  rather than bounded, and the page grows in place the way every other long list
  in the application does.

  @var \App\ViewModels\Vaults\VaultRemindersSentViewModel $viewModel
--}}
@php
  $rows = $viewModel->rows();
@endphp
<x-shell :title="$viewModel->title()">
  <x-slot:breadcrumb>
    <x-breadcrumb
      :items="[
        ['label' => $viewModel->vault()->name, 'url' => route('vaults.show', $viewModel->vault())],
        ['label' => __('Reminders')],
      ]"
    />
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-vault.sidebar :user="$viewModel->user()" :vault="$viewModel->vault()" current="reminders" />
  </x-slot:sidebar>

  <x-page-header :title="$viewModel->title()" :description="$viewModel->intro()" />

  @include('app.vaults.reminders._tabs', [
    'activeUrl' => $viewModel->activeUrl(),
    'sentUrl' => $viewModel->sentUrl(),
    'activeCount' => $viewModel->activeCount(),
    'sentCount' => $viewModel->sentCount(),
    'current' => 'sent',
  ])

  <x-panel :flush="true">
    <div id="sent-rows" x-merge="append">
      @forelse ($rows as $row)
        @include('app.vaults.reminders._sent-row', ['row' => $row])
      @empty
        <x-empty-state :title="$viewModel->emptyTitle()">
          <x-slot:icon>
            <x-icon name="reminder" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ $viewModel->emptyLine() }}
        </x-empty-state>
      @endforelse
    </div>

    @if ($viewModel->hasMorePages())
      <x-slot:footer id="sent-pagination">
        <div class="text-center">
          <x-link x-target="sent-rows sent-pagination" :href="$viewModel->nextPageUrl()">{{ __('Load more') }}</x-link>
        </div>
      </x-slot:footer>
    @endif
  </x-panel>
</x-shell>
