{{-- One channel: what it carries, what it still asks for, and the switch that turns it on. --}}
{{--
  @var array $channel
  @var int $working
--}}
@php
  $on = $channel['is_enabled'];
  $ready = $channel['is_ready'];
  $switch = $channel['channel'].'-enabled';
  $heading = 'channel-'.$channel['channel'].'-heading';
@endphp

<x-panel
  :title="$channel['label']"
  :title-id="$heading"
  :subtitle="$channel['tagline']"
  :icon="$channel['icon']"
  :aria-labelledby="$heading"
>
  <x-slot:action>
    <x-form method="put" :action="$channel['update_url']" x-target="notification-channels" class="flex items-center gap-3">
      <input type="hidden" name="is_enabled" value="0" />

      <span class="text-xs font-medium text-body">{{ $channel['state'] }}</span>

      <label for="{{ $switch }}" class="flex cursor-pointer items-center">
        <x-switch
          :id="$switch"
          name="is_enabled"
          :checked="$on"
          :disabled="! $ready && ! $on"
          :aria-label="__('Send notifications on this channel')"
          x-on:change="$el.form.requestSubmit()"
        />
      </label>
    </x-form>
  </x-slot:action>

  <div class="flex flex-col gap-4">
    @if ($channel['disabled_reason'] !== null)
      <x-notice tone="warning">
        {{ __('We switched this off because nothing was getting through. What came back was: :reason', ['reason' => $channel['disabled_reason']]) }}
      </x-notice>
    @endif

    <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] md:items-start">
      <div class="flex min-w-0 flex-col gap-3.5">
        <p class="text-sm text-pretty text-body">{{ $channel['summary'] }}</p>

        <div class="flex flex-col gap-2">
          <x-caption>{{ __('What lands here') }}</x-caption>

          <ul class="flex flex-col gap-1.5">
            @foreach ($channel['carries'] as $item)
              <li class="flex items-center gap-2 text-xs text-body">
                <x-icon :name="$item['icon']" :size="15" :stroke-width="1.5" class="text-muted" />
                {{ $item['label'] }}
              </li>
            @endforeach
          </ul>
        </div>

        @unless ($ready)
          <div class="flex flex-col gap-2 rounded-md border border-hairline bg-card p-3">
            <x-caption>{{ __('Before you can switch this on') }}</x-caption>

            <ol class="flex list-decimal flex-col gap-1 pl-4 text-xs text-body">
              @foreach ($channel['steps'] as $step)
                <li>{{ $step }}</li>
              @endforeach
            </ol>

            @include('app.settings.account.notifications._setup', ['channel' => $channel])
          </div>
        @else
          <div class="flex flex-wrap items-center gap-2">
            <x-form method="post" :action="$channel['test_url']">
              <x-button type="submit" variant="secondary" size="small">{{ __('Send a test') }}</x-button>
            </x-form>

            @if ($channel['discovers_destination'])
              <x-form method="post" :action="$channel['connection_url']" data-turbo="false">
                <x-button type="submit" variant="secondary" size="small">{{ __('Connect a different conversation') }}</x-button>
              </x-form>
            @endif

            @if ($channel['requires_proof'])
              <x-form method="post" :action="$channel['reset_url']">
                <x-button type="submit" variant="secondary" size="small">{{ __('Cancel this connection') }}</x-button>
              </x-form>
            @endif
          </div>

          @if ($channel['discovers_destination'])
            <p class="text-xs text-body">{{ __('Connected. Notifications go to the conversation you opened with our bot.') }}</p>
          @endif

          @if ($channel['requires_proof'])
            <p class="text-xs text-body">{{ __('Your destination is set: :destination.', ['destination' => $channel['destination']]) }}</p>
          @endif
        @endunless
      </div>

      @include('app.settings.account.notifications._preview', ['channel' => $channel])
    </div>
  </div>

  @if ($on && $working === 1)
    <x-slot:footer class="flex items-start gap-2.5 text-xs text-body">
      <x-icon name="info" :size="16" :stroke-width="1.5" class="mt-px text-warning" />
      {{ __('This is the only channel that still works. Switching it off means nothing reaches you at all.') }}
    </x-slot:footer>
  @endif
</x-panel>
