{{-- Everything written down about an account: what its owner did, and what we sent them. --}}
{{-- @var \App\ViewModels\Settings\Account\Logs\LogsViewModel $viewModel --}}
<x-shell :title="__('Logs')">
  <x-slot:breadcrumb>
    <x-button.bar :href="route('settings.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="logs" />
  </x-slot:sidebar>

  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Logs') }}</h1>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('What has been done in this account, and every email it has sent.') }}</p>
  </header>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('Activity') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('Every action that touched this account, newest first, with where it came from.') }}</p>
    </div>

    <x-panel :flush="true">
      <div id="logs-container" x-merge="append">
        @forelse ($viewModel->logs() as $log)
          @include('app.settings.account.logs._log-row', ['log' => $log])
        @empty
          <x-empty-state :title="__('Nothing has happened yet')">
            <x-slot:icon>
              <x-icon name="timeline" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ __('Every change to your account is written down here, with the time it happened and the address it came from.') }}
          </x-empty-state>
        @endforelse
      </div>

      @if ($viewModel->logs()->hasMorePages())
        <x-slot:footer id="pagination">
          <div class="text-center">
            <x-link x-target="logs-container pagination" :href="$viewModel->logs()->nextPageUrl()">{{ __('Load more') }}</x-link>
          </div>
        </x-slot:footer>
      @endif
    </x-panel>
  </section>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 text-section-title text-ink">{{ __('Emails sent') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('If one you expected never arrived, look here first. An entry still on its way means the mail service has not confirmed it yet. No entry at all means it was never sent.') }}</p>
    </div>

    <x-panel :flush="true">
      @forelse ($viewModel->emailsSent() as $emailSent)
        @include('app.settings.account.logs._email-sent-row', ['emailSent' => $emailSent])
      @empty
        <x-empty-state :title="__('No email has been sent yet')">
          <x-slot:icon>
            <x-icon name="mail" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('The emails we send you, such as sign-in links and password changes, appear here once they are sent, with whether they arrived.') }}
        </x-empty-state>
      @endforelse

      @if ($viewModel->hasMoreEmailsSent())
        <x-slot:footer>
          <div class="text-center">
            <x-link :href="route('settings.emailsSent.index')" turbo>{{ __('Browse all emails') }}</x-link>
          </div>
        </x-slot:footer>
      @endif
    </x-panel>
  </section>
</x-shell>
