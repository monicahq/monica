{{--
  Everything that has been written down about an account: what its owner has
  done, and what we have sent them.

  Two lists, so two sections. Neither is something somebody can add to, which is
  why neither has an action: this is a screen for reading, and the only thing to do
  on it is ask for more of it.

  The next page of logs is appended in place, and the link that asked for it is
  replaced by the one that came back, or removed on the last page.

  @var \App\ViewModels\Settings\Account\Logs\LogsViewModel $viewModel
--}}
<x-shell :title="__('Logs')">
  <x-slot:bar>
    <x-button.secondary :href="route('settings.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to settings') }}
    </x-button.secondary>
  </x-slot:bar>

  <x-slot:dock>
    <x-dock :user="$viewModel->user()" />
  </x-slot:dock>

  <header class="flex flex-col gap-2">
    <h1 class="font-display text-page-title text-ink">{{ __('Logs') }}</h1>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('What has been done in this account, and every email it has sent.') }}</p>
  </header>

  <section class="flex flex-col gap-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-0.5">
      <h2 class="shrink-0 font-display text-section-title text-ink">{{ __('Activity') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('Every action that touched this account, newest first, with where it came from.') }}</p>
    </div>

    <x-panel :flush="true">
      <div id="logs-container" x-merge="append">
        @forelse ($viewModel->logs() as $log)
          @include('app.settings.account.logs._log-row', ['log' => $log])
        @empty
          <x-empty-state :title="__('Nothing has happened yet')" icon-class="bg-forest-tint text-forest">
            <x-slot:icon>
              <x-icon name="timeline" :size="30" :stroke-width="1.4" />
            </x-slot:icon>

            {{ __('As soon as you change something on your account, it is written down here, with the moment it happened and the address it came from.') }}
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
      <h2 class="shrink-0 font-display text-section-title text-ink">{{ __('Emails sent') }}</h2>

      <p class="text-sm text-pretty text-muted">{{ __('If one you expected never arrived, look here first: an entry still on its way means the mail service has not confirmed it yet, and no entry at all means it was never sent.') }}</p>
    </div>

    <x-panel :flush="true">
      @forelse ($viewModel->emailsSent() as $emailSent)
        @include('app.settings.account.logs._email-sent-row', ['emailSent' => $emailSent])
      @empty
        <x-empty-state :title="__('Nothing has left our hands yet')" icon-class="bg-plum-tint text-plum">
          <x-slot:icon>
            <x-icon name="mail" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('The emails we send you, such as sign-in links and password changes, show up here once they do, along with whether they arrived.') }}
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
