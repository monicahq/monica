{{-- How the instance is doing, and the first thing an administrator sees. --}}
{{-- @var \App\ViewModels\Admin\OverviewViewModel $viewModel --}}
@use('App\Enums\EmailDeliveryStatusEnum')
<x-admin-layout :title="__('Overview')">
  <x-page-header :title="__('Overview')" :description="__('Where this instance stands, counted when you opened this screen.')" />

  @if ($viewModel->isEmpty())
    <x-notice>
      {{ __('Nobody has signed up on this instance yet. Every figure below is zero because there is nothing to count, not because anything is wrong.') }}
    </x-notice>
  @endif

  @if ($viewModel->awaitingModeration() > 0)
    <x-notice tone="warning">
      <x-slot:action>
        <x-button.secondary :href="route('admin.moderation.index')" data-turbo="true" size="small">{{ __('Open the queue') }}</x-button.secondary>
      </x-slot:action>

      {{ trans_choice(':count marketplace submission is waiting for a decision.|:count marketplace submissions are waiting for a decision.', $viewModel->awaitingModeration()) }}
    </x-notice>
  @endif

  <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ([
      ['label' => __('Accounts'), 'value' => $viewModel->accountCount(), 'period' => __('all time')],
      ['label' => __('People'), 'value' => $viewModel->userCount(), 'period' => __('all time')],
      ['label' => __('Vaults'), 'value' => $viewModel->vaultCount(), 'period' => __('all time')],
      ['label' => __('Contacts'), 'value' => $viewModel->contactCount(), 'period' => __('all time')],
      ['label' => __('Storage held'), 'value' => $viewModel->storageUsed(), 'period' => __('all time')],
      ['label' => __('Emails sent'), 'value' => $viewModel->emailsSentCount(), 'period' => __('all time')],
      ['label' => __('Submissions waiting'), 'value' => $viewModel->awaitingModeration(), 'period' => __('right now')],
      ['label' => __('Signed in'), 'value' => $viewModel->signedInRecently(), 'period' => trans_choice('last :count day|last :count days', \App\ViewModels\Admin\OverviewViewModel::SIGNED_IN_DAYS)],
      ['label' => __('New accounts'), 'value' => $viewModel->accountsCreatedRecently(), 'period' => trans_choice('last :count day|last :count days', \App\ViewModels\Admin\OverviewViewModel::NEW_ACCOUNT_DAYS)],
    ] as $figure)
      <div class="rounded-lg border border-hairline bg-card px-4 py-3.5 ">
        <x-caption class="block">{{ $figure['label'] }}</x-caption>

        <p class="text-section-title text-ink">{{ $figure['value'] }}</p>

        <p class="text-xs text-muted">{{ $figure['period'] }}</p>
      </div>
    @endforeach
  </section>

  <section class="flex flex-col gap-3">
    <h2 class="text-section-title text-ink">{{ __('Where the emails got to') }}</h2>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
      @foreach ([
        [EmailDeliveryStatusEnum::Delivered, __('Delivered')],
        [EmailDeliveryStatusEnum::Bounced, __('Bounced')],
        [EmailDeliveryStatusEnum::Complained, __('Marked as spam')],
        [EmailDeliveryStatusEnum::Sent, __('On its way')],
      ] as [$status, $label])
        <div class="rounded-lg border border-hairline bg-card px-4 py-3.5">
          <x-caption class="block">{{ $label }}</x-caption>

          <p class="text-section-title text-ink">{{ $viewModel->emailsSentByStatus()[$status->value] }}</p>

          <p class="text-xs text-muted">{{ __('all time') }}</p>
        </div>
      @endforeach
    </div>
  </section>
</x-admin-layout>
