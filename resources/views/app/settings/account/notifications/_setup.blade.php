{{-- What a channel still asks for before it may be switched on: a conversation, a number, or a code typed back. --}}
{{-- @var array $channel --}}
@if ($channel['discovers_destination'] && ! $channel['is_connected'])
  <x-form method="post" :action="$channel['connection_url']">
    <x-button type="submit" variant="secondary" size="small">{{ __('Connect Telegram') }}</x-button>
  </x-form>
@endif

@if ($channel['requires_proof'] && ! $channel['is_proven'])
  @if (! $channel['awaits_proof'])
    <x-form method="post" :action="$channel['proof_url']" class="flex flex-col gap-2">
      @if ($channel['needs_destination'] && ! $channel['discovers_destination'])
        <x-input
          :id="$channel['channel'] . '-destination'"
          name="destination"
          :label="$channel['destination_label']"
          :value="$channel['destination']"
          :help="__('Only put a number that is yours alone. Anybody who can read it will read everything the application tells you.')"
        />
      @endif

      <x-button type="submit" variant="secondary" size="small" class="self-start">{{ __('Send me a code') }}</x-button>
    </x-form>
  @else
    <div class="flex flex-col gap-2">
      <p class="text-xs text-pretty text-body">
        {{ __('A code has been sent to your destination :destination. Please enter it below to confirm.', ['destination' => $channel['destination']]) }}
      </p>

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
    </div>
  @endif
@endif
