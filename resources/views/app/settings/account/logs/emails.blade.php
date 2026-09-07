{{-- Every email we sent to the person signed in, a page at a time. --}}
{{-- @var \App\ViewModels\Settings\Account\Logs\EmailsSentViewModel $viewModel --}}
<x-shell :title="__('Emails sent')">
  <x-slot:breadcrumb>
    <x-button.bar :href="route('settings.logs.index')" data-turbo="true" size="small">
      <x-icon name="chevron-right" :size="15" :stroke-width="1.7" class="rotate-180" />
      {{ __('Back to the logs') }}
    </x-button.bar>
  </x-slot:breadcrumb>

  <x-slot:sidebar>
    <x-settings.sidebar :user="$viewModel->user()" current="logs" />
  </x-slot:sidebar>

  <header class="flex flex-col gap-2">
    <h1 class="text-page-title text-ink">{{ __('Emails sent') }}</h1>

    <p class="max-w-[56ch] text-base text-pretty text-body">{{ __('Every email we sent to your account, most recent first. Open one to read what was sent.') }}</p>
  </header>

  <x-panel :flush="true">
    <div id="emails-sent-container" x-merge="append">
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
    </div>

    @if ($viewModel->emailsSent()->hasMorePages())
      <x-slot:footer id="pagination">
        <div class="text-center">
          <x-link x-target="emails-sent-container pagination" :href="$viewModel->emailsSent()->nextPageUrl()">{{ __('Load more') }}</x-link>
        </div>
      </x-slot:footer>
    @endif
  </x-panel>
</x-shell>
