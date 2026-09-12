{{-- Every way this account can be reached, what each one still needs, and whether it is switched on. --}}
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
    <x-page-header :title="__('Notifications')" :description="__('Where the application may reach you.')" />

    @if ($errors->any())
      <x-notice tone="danger">
        <ul class="flex flex-col gap-1">
          @foreach ($errors->all() as $message)
            <li>{{ $message }}</li>
          @endforeach
        </ul>
      </x-notice>
    @endif

    @foreach ($channels as $channel)
      @if ($channel['is_configured_on_the_instance'])
        <section
          aria-labelledby="channel-{{ $channel['channel'] }}-heading"
          class="flex flex-col gap-4 rounded-lg border border-hairline bg-card p-4"
        >
          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h2 id="channel-{{ $channel['channel'] }}-heading" class="text-section-title text-ink">{{ $channel['label'] }}</h2>

            <span class="text-[12px] text-muted">{{ $channel['state'] }}</span>
          </div>

          @if ($channel['disabled_reason'] !== null)
            <x-notice tone="warning">
              {{ __('We switched this off because nothing was getting through. What came back was: :reason', ['reason' => $channel['disabled_reason']]) }}
            </x-notice>
          @endif

          <ol class="flex list-decimal flex-col gap-1 pl-5 text-[13px] text-body">
            @foreach ($channel['steps'] as $step)
              <li>{{ $step }}</li>
            @endforeach
          </ol>

          <div class="flex flex-wrap items-center gap-2">
            @if ($channel['discovers_destination'])
              <x-form method="post" :action="$channel['connection_url']">
                <x-button type="submit" variant="secondary" size="small">
                  {{ $channel['is_connected'] ? __('Connect a different conversation') : __('Connect Telegram') }}
                </x-button>
              </x-form>
            @endif

            @if ($channel['requires_proof'] && $channel['is_proven'])
              <p class="text-[13px] text-body">{{ __('Your destination is set: :destination.', ['destination' => $channel['destination']]) }}</p>
              <x-form method="post" :action="$channel['reset_url']">
                <x-button type="submit" variant="secondary" size="small">{{ __('Cancel this connection') }}</x-button>
              </x-form>
            @endif

            @if ($channel['is_ready'])
              <x-form method="post" :action="$channel['test_url']">
                <x-button type="submit" variant="secondary" size="small">{{ __('Send a test') }}</x-button>
              </x-form>
            @endif
          </div>

          @if ($channel['requires_proof'] && ! $channel['is_proven'])
            <div class="flex flex-col gap-2 rounded-md border border-hairline bg-canvas p-3">
              @if (! $channel['awaits_proof'])
                <x-form method="post" :action="$channel['proof_url']">
                @if ($channel['needs_destination'] && ! $channel['discovers_destination'])
                  <x-input
                    :id="$channel['channel'] . '-destination'"
                    name="destination"
                    :label="$channel['destination_label']"
                    :value="$channel['destination']"
                    :help="__('Only put a number that is yours alone. Anybody who can read it will read everything the application tells you.')"
                  />
                @endif

                  <x-button type="submit" variant="secondary" size="small">{{ __('Send me a code') }}</x-button>
                </x-form>
              @else
                {{ __('A code has been sent to your destination :destination. Please enter it below to confirm.', ['destination' => $channel['destination']]) }}
                <x-form method="put" :action="$channel['proof_confirm_url']">
                  <div class="flex flex-wrap items-end gap-2">
                    <x-input
                      :id="$channel['channel'] . '-code'"
                      name="code"
                      :label="__('The code')"
                      inputmode="numeric"
                      autocomplete="one-time-code"
                    />

                    <x-button type="submit" size="small">{{ __('Confirm') }}</x-button>
                  </div>
                </x-form>
              @endif
            </div>
          @endif

          <x-form method="put" :action="$channel['update_url']">
            <div class="flex flex-col gap-3">
              @if ($channel['discovers_destination'])
                <div class="flex flex-col gap-2 rounded-md border border-hairline bg-canvas p-3">
                  @if ($channel['is_connected'])
                    <p class="text-[13px] text-body">{{ __('Connected. Notifications go to the conversation you opened with our bot.') }}</p>
                  @else
                    <p class="text-[13px] text-body">{{ __('Follow the link, then press Start in the conversation that opens. That is all: the bot tells us where to send.') }}</p>
                  @endif
                </div>
              @endif

              <x-checkbox :id="$channel['channel'] . '-enabled'" name="is_enabled" value="1" :checked="$channel['is_enabled']">
                {{ __('Send notifications on this channel') }}
              </x-checkbox>

              <div class="flex flex-wrap items-center gap-2">
                <x-button type="submit" size="small">{{ __('Save') }}</x-button>
              </div>
            </div>
          </x-form>

          @if ($channel['is_enabled'] && $working === 1)
            <p class="text-[12px] text-muted">{{ __('This is the only channel that still works. Switching it off means nothing reaches you at all.') }}</p>
          @endif
        </section>
      @endif
    @endforeach
  </div>
</x-settings.page>
