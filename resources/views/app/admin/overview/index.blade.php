{{-- How the instance is doing, and the first thing an administrator sees. --}}
{{-- @var \App\ViewModels\Admin\OverviewViewModel $viewModel --}}
@use('App\Enums\EmailDeliveryStatusEnum')
<x-admin-layout :title="__('Overview')">
  <x-page-header :title="__('Overview')">
    <x-slot:actions>
      <span class="flex items-center gap-1.5 font-mono text-xs text-muted">
        <x-icon name="clock" :size="15" />
        <x-timestamp :at="$viewModel->countedAtMoment()">{{ __('Counted at :time', ['time' => $viewModel->countedAt()]) }}</x-timestamp>
      </span>

      <x-button.secondary :href="route('admin.overview.index')" data-turbo="true" size="small">{{ __('Count again') }}</x-button.secondary>
    </x-slot:actions>
  </x-page-header>

  @if ($viewModel->isEmpty())
    <x-notice>
      {{ __('Nobody has signed up on this instance yet. Every figure below is zero because there is nothing to count, not because anything is wrong.') }}
    </x-notice>
  @endif

  <x-page-section :title="__('What the instance holds')">
    <dl class="grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-hairline bg-hairline shadow-panel min-[22.5rem]:grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
      @foreach ([
        ['label' => __('Accounts'), 'value' => $viewModel->accountCount(), 'icon' => 'profile'],
        ['label' => __('People'), 'value' => $viewModel->userCount(), 'icon' => 'people'],
        ['label' => __('Vaults'), 'value' => $viewModel->vaultCount(), 'icon' => 'vault'],
        ['label' => __('Contacts'), 'value' => $viewModel->contactCount(), 'icon' => 'relationship'],
        ['label' => __('Storage held'), 'value' => $viewModel->storageUsed(), 'icon' => 'layers'],
        ['label' => __('Emails sent'), 'value' => $viewModel->emailsSentCount(), 'icon' => 'mail'],
      ] as $figure)
        <div class="flex flex-col gap-1.5 bg-canvas p-4">
          <dt>
            <x-caption tone="muted" class="flex items-center gap-1.5">
              <x-icon :name="$figure['icon']" :size="15" />
              {{ $figure['label'] }}
            </x-caption>
          </dt>

          <dd class="text-[22px] font-semibold tracking-[-0.02em] whitespace-nowrap text-ink tabular-nums">{{ $figure['value'] }}</dd>
        </div>
      @endforeach
    </dl>

    <p class="text-xs text-muted">{{ __('All time, since the instance was installed.') }}</p>
  </x-page-section>

  <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,380px),1fr))] gap-6">
    <x-panel :title="__('Waiting for you')" :subtitle="__('Submissions people sent in that need a decision')" icon="tasks">
      <div class="flex flex-wrap items-center gap-4">
        <span aria-hidden="true" class="text-[22px] font-semibold text-ink tabular-nums">{{ $viewModel->awaitingModeration() }}</span>

        <span class="text-body">{{ $viewModel->moderationSentence() }}</span>

        <x-link :href="route('admin.moderation.index')" class="ml-auto">{{ __('Open moderation') }}</x-link>
      </div>
    </x-panel>

    <x-panel :title="__('Coming and going')" :subtitle="__('Who used the instance lately')" icon="activity">
      <dl class="grid grid-cols-2 gap-4">
        <div class="flex flex-col-reverse gap-0.5">
          <dt class="text-body">{{ $viewModel->signedInLabel() }}</dt>
          <dd class="text-[22px] font-semibold text-ink tabular-nums">{{ $viewModel->signedInRecently() }}</dd>
        </div>

        <div class="flex flex-col-reverse gap-0.5">
          <dt class="text-body">{{ $viewModel->newAccountsLabel() }}</dt>
          <dd class="text-[22px] font-semibold text-ink tabular-nums">{{ $viewModel->accountsCreatedRecently() }}</dd>
        </div>
      </dl>
    </x-panel>
  </div>

  <x-panel :title="__('Where the emails got to')" :subtitle="__('Every email the instance has sent, by what the mail provider reported back')" icon="mail">
    @php
      $hatch = 'bg-[repeating-linear-gradient(-45deg,var(--muted-soft)_0_4px,var(--hairline-strong)_4px_8px)]';

      $statuses = [
        [EmailDeliveryStatusEnum::Delivered, __('Delivered'), 'bg-success'],
        [EmailDeliveryStatusEnum::Bounced, __('Bounced'), 'bg-error'],
        [EmailDeliveryStatusEnum::Complained, __('Marked as spam'), 'bg-warning'],
        [EmailDeliveryStatusEnum::Sent, __('On its way'), $hatch],
      ];
    @endphp

    <div class="flex flex-col gap-4">
      <div role="img" aria-label="{{ $viewModel->emailSummary() }}" class="flex h-2.5 overflow-hidden rounded-xs bg-hairline-soft">
        @foreach ($statuses as [$status, $label, $fill])
          @if ($viewModel->emailShares()[$status->value] > 0)
            <span class="{{ $fill }}" style="width: {{ $viewModel->emailShares()[$status->value] }}%"></span>
          @endif
        @endforeach
      </div>

      <dl class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-4">
        @foreach ($statuses as [$status, $label, $fill])
          <div class="flex flex-col gap-1">
            <dt class="flex items-center gap-2 text-body">
              <span aria-hidden="true" class="size-2.5 shrink-0 rounded-xs {{ $fill }}"></span>
              {{ $label }}
            </dt>

            <dd class="text-lg font-semibold text-ink tabular-nums">{{ $viewModel->emailsSentByStatus()[$status->value] }}</dd>
          </div>
        @endforeach
      </dl>
    </div>
  </x-panel>
</x-admin-layout>
