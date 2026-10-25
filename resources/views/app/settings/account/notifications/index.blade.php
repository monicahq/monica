{{-- Every way this account can be reached, what each one carries, and whether it is switched on. --}}
{{-- @var \App\ViewModels\Settings\Account\Notifications\NotificationChannelsViewModel $viewModel --}}
@php
  $channels = $viewModel->channels();
  $working = $viewModel->workingCount();
@endphp
<x-settings.page
  :title="__('Notifications')"
  :user="$viewModel->user()"
  current="notifications"
>
  <div class="flex flex-col gap-8">
    <x-page-header :title="__('Notifications')" />

    <div id="notification-channels" class="flex flex-col gap-6">
      @if ($errors->any())
        <x-notice tone="danger">
          <ul class="flex flex-col gap-1">
            @foreach ($errors->all() as $message)
              <li>{{ $message }}</li>
            @endforeach
          </ul>
        </x-notice>
      @endif

      @php($keptNumberRemoval = $viewModel->keptNumberRemoval())

      @if ($keptNumberRemoval !== null)
        <x-notice tone="quiet">
          <div class="flex flex-col items-start gap-2">
            <p>{{ $viewModel->smsNotice() }}</p>

            <x-delete-action
              :title="__('Remove :name?', ['name' => __('Text message')])"
              :confirm="__('Remove')"
              :action="$keptNumberRemoval['url']"
              :label="__('Remove the number')"
              size="small"
            >
              <p>{{ $keptNumberRemoval['warning'] }}</p>
            </x-delete-action>
          </div>
        </x-notice>
      @endif

      @foreach ($channels as $channel)
        @if ($channel['is_configured_on_the_instance'])
          @include('app.settings.account.notifications._channel', ['channel' => $channel, 'working' => $working])
        @endif
      @endforeach

      @if ($working === 0)
        <x-notice tone="quiet">
          {{ __('Nothing is switched on. The application keeps recording your reminders, and you will see them only when you open it yourself.') }}
        </x-notice>
      @endif
    </div>
  </div>
</x-settings.page>
