{{--
  How the instance is doing, and the first thing an administrator sees.

  Every figure says what period it covers. A count with no period is a count
  somebody reads as whatever they were expecting it to be, and two of these cover
  a window rather than the whole history.

  An empty instance shows zeros and says it is empty. Zeros with no explanation
  read as a screen that failed to load, and the first thing somebody does after
  installing this is open it.

  The submissions waiting is the only figure here that is a job rather than a fact,
  so it is the only one that leads anywhere.

  The emails have a section of their own because they are one figure split four
  ways rather than four unrelated figures, and a total sitting in the grid beside
  its own parts reads as a fifth part. Each state is named in words, never by a
  tint, so the breakdown carries no meaning an administrator has to see in colour.

  @var \App\ViewModels\Admin\OverviewViewModel $viewModel
--}}
@use('App\Enums\EmailDeliveryStatusEnum')
<x-admin-layout :title="__('Overview')">
  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Overview') }}</h1>

    <p class="max-w-[64ch] text-base text-pretty text-body">{{ __('Where this instance stands, counted the moment you opened this screen.') }}</p>
  </header>

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
        <p class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ $figure['label'] }}</p>

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
          <p class="text-[11px] font-medium tracking-caption text-muted-soft uppercase">{{ $label }}</p>

          <p class="text-section-title text-ink">{{ $viewModel->emailsSentByStatus()[$status->value] }}</p>

          <p class="text-xs text-muted">{{ __('all time') }}</p>
        </div>
      @endforeach
    </div>
  </section>
</x-admin-layout>
