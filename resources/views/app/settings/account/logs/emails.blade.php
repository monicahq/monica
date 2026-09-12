{{-- Every email we sent to the person signed in, a page at a time. --}}
{{-- @var \App\ViewModels\Settings\Account\Logs\EmailsSentViewModel $viewModel --}}
<x-settings.page
  :title="__('Emails sent')"
  :user="$viewModel->user()"
  current="logs"
  :back-href="route('settings.logs.index')"
  :back-label="__('Back to the logs')"
>
  <x-page-header :title="__('Emails sent')" :description="__('Every email we sent to your account, most recent first. Open one to read what was sent.')" />

  <x-list-panel
    :items="$viewModel->emailsSent()"
    id="emails-sent-container"
    merge="append"
    target="emails-sent-container pagination"
    footer-id="pagination"
    :next-page-url="$viewModel->emailsSent()->hasMorePages() ? $viewModel->emailsSent()->nextPageUrl() : null"
  >
    <x-slot:empty>
      <x-empty-state :title="__('No email has been sent yet')">
        <x-slot:icon>
          <x-icon name="mail" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('The emails we send you, such as sign-in links and password changes, appear here once they are sent, with whether they arrived.') }}
      </x-empty-state>
    </x-slot:empty>

    @foreach ($viewModel->emailsSent() as $emailSent)
      @include('app.settings.account.logs._email-sent-row', ['emailSent' => $emailSent])
    @endforeach
  </x-list-panel>
</x-settings.page>
