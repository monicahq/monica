{{-- Everything written down about an account: what its owner did, and what we sent them. --}}
{{-- @var \App\ViewModels\Settings\Account\Logs\LogsViewModel $viewModel --}}
<x-settings.page
  :title="__('Logs')"
  :user="$viewModel->user()"
  current="logs"
>
  <x-page-header :title="__('Logs')" :description="__('What has been done in this account, and every email it has sent.')" />

  <x-page-section :title="__('Activity')" :description="__('Every action that touched this account, newest first, with where it came from.')">
    <x-list-panel
      :items="$viewModel->logs()"
      id="logs-container"
      merge="append"
      target="logs-container pagination"
      footer-id="pagination"
      :next-page-url="$viewModel->logs()->hasMorePages() ? $viewModel->logs()->nextPageUrl() : null"
    >
      <x-slot:empty>
        <x-empty-state :title="__('Nothing has happened yet')">
          <x-slot:icon>
            <x-icon name="timeline" :size="30" :stroke-width="1.4" />
          </x-slot:icon>

          {{ __('Every change to your account is written down here, with the time it happened and the address it came from.') }}
        </x-empty-state>
      </x-slot:empty>

      @foreach ($viewModel->logs() as $log)
        @include('app.settings.account.logs._log-row', ['log' => $log])
      @endforeach
    </x-list-panel>
  </x-page-section>

  <x-page-section :title="__('Emails sent')" :description="__('If one you expected never arrived, look here first. An entry still on its way means the mail service has not confirmed it yet. No entry at all means it was never sent.')">
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
  </x-page-section>
</x-settings.page>
