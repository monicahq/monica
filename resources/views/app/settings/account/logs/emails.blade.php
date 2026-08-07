{{--
  Every email we sent to the person signed in, a page at a time.

  The list grows in place: the link at the bottom asks for the next page,
  alpine-ajax appends the rows that come back, and swaps the link for the one
  that came with them, or drops it on the last page.

  @var \App\ViewModels\Settings\Account\Logs\EmailsSentViewModel $viewModel
--}}
<x-app-layout :title="__('Emails sent')">
  <x-slot:sidebar>
    <x-settings.sidebar :company-name="$viewModel->companyName()" :name="$viewModel->name()" :employee="$viewModel->employee()" current="logs" />
  </x-slot:sidebar>

  <x-slot:breadcrumb>
    <nav class="text-sm text-muted" aria-label="{{ __('Breadcrumb') }}">
      {{ __('Settings') }}
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <x-link :href="route('settings.logs.index')" turbo>{{ __('Logs') }}</x-link>
      <span class="px-1 text-placeholder" aria-hidden="true">/</span>
      <span class="text-ink" aria-current="page">{{ __('Emails sent') }}</span>
    </nav>
  </x-slot:breadcrumb>

  <x-page-header
    :title="__('Emails sent')"
    :description="__('Every email we sent to your account, most recent first.')"
  />

  <x-box id="emails-sent-container" x-merge="append" padding="p-0">
    @forelse ($viewModel->emailsSent() as $emailSent)
      @include('app.settings.account.logs._email-sent-row', ['emailSent' => $emailSent])
    @empty
      <x-empty-state :title="__('No emails yet')">
        <x-slot:icon>
          <svg width="22" height="22" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
            <rect x="1.5" y="3.5" width="13" height="9" rx="1.5"></rect>
            <path d="m2 4.5 6 4.5 6-4.5"></path>
          </svg>
        </x-slot:icon>

        {{ __('Nothing has left our hands yet. The emails we send you, such as sign-in links and password changes, show up here once they do.') }}
      </x-empty-state>
    @endforelse

    @if ($viewModel->emailsSent()->hasMorePages())
      <x-slot:footer id="pagination">
        <x-link x-target="emails-sent-container pagination" :href="$viewModel->emailsSent()->nextPageUrl()">{{ __('Load more') }}</x-link>
      </x-slot:footer>
    @endif
  </x-box>
</x-app-layout>
