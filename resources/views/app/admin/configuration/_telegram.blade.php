{{-- The Telegram block of the configuration screen: registering the bot with Telegram. --}}
{{-- @var \App\ViewModels\Admin\TelegramRegistrationPanel $panel --}}
<x-page-section
  :title="__('Telegram bot')"
  :description="__('Telegram only passes on what people say to the bot once it knows where this instance is.')"
>
  <div class="flex flex-col gap-3 rounded-lg border border-hairline bg-card px-4 py-3.5 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
    <div class="min-w-0 space-y-1 sm:max-w-md">
      <p role="status" class="text-label text-ink">
        @if ($panel->registration())
          <x-timestamp :at="$panel->registration()->registered_at">{{ $panel->statusSentence() }}</x-timestamp>
        @else
          {{ $panel->statusSentence() }}
        @endif
      </p>

      <p class="flex flex-wrap items-baseline gap-1.5">
        <x-caption>{{ __('Address given to Telegram') }}</x-caption>
        <x-code-value>{{ $panel->address() }}</x-code-value>
      </p>

      @if ($panel->unavailableReason())
        <p id="telegram-unavailable" class="text-xs text-muted">{{ $panel->unavailableReason() }}</p>
      @endif

      @if (session('telegram_check'))
        @php($check = session('telegram_check'))
        <div role="status" class="space-y-1 pt-1 text-xs text-muted">
          @if ($check['url'] === null)
            <p>{{ __('Telegram sends what people say to the bot nowhere at the moment.') }}</p>
          @elseif ($check['matches'])
            <p>{{ __('Telegram sends what people say to the bot to this instance.') }}</p>
          @else
            <p>{{ __('Telegram sends what people say to the bot to :address, not to this instance.', ['address' => $check['url']]) }}</p>
          @endif

          @if ($check['lastError'])
            <p>{{ __('The last delivery failed: :error', ['error' => $check['lastError']]) }}</p>
          @endif

          <p>{{ __('Telegram does not say which secret it holds, so this cannot confirm it.') }}</p>
        </div>
      @endif

      @error('telegram')
        <p role="alert" class="text-xs text-error">{{ $message }}</p>
      @enderror
    </div>

    <div class="flex shrink-0 flex-wrap items-start gap-2">
      <form
        method="POST"
        action="{{ route('admin.configuration.telegram.secret.create') }}"
        x-data="{ confirming: false }"
        class="flex flex-col items-end gap-2"
      >
        @csrf

        @if (! $panel->canGenerateSecret())
          <x-button.secondary
            type="submit"
            size="small"
            aria-disabled="true"
            aria-describedby="telegram-unavailable"
            onclick="return false"
          >
            {{ __('Generate a secret') }}
          </x-button.secondary>
        @elseif ($panel->secretReplacementWarning())
          <x-button.secondary
            type="submit"
            size="small"
            @click.prevent="if (! confirming) { confirming = true } else { $dispatch('confirm-password', { form: $el.form }) }"
          >
            {{ __('Generate a secret') }}
          </x-button.secondary>
        @else
          <x-button.secondary
            type="submit"
            size="small"
            @click.prevent="$dispatch('confirm-password', { form: $el.form })"
          >
            {{ __('Generate a secret') }}
          </x-button.secondary>
        @endif

        @if ($panel->secretReplacementWarning())
          <p x-cloak x-show="confirming" role="alert" class="max-w-xs text-end text-xs text-error">
            {{ $panel->secretReplacementWarning() }} {{ __('Generate again to go ahead.') }}
          </p>
        @endif
      </form>

      <form method="POST" action="{{ route('admin.configuration.telegram.check.create') }}">
        @csrf

        <x-button.secondary
          type="submit"
          size="small"
          :aria-disabled="$panel->canBeChecked() ? null : 'true'"
          :aria-describedby="$panel->canBeChecked() ? null : 'telegram-unavailable'"
          :onclick="$panel->canBeChecked() ? null : 'return false'"
        >
          {{ __('Check with Telegram') }}
        </x-button.secondary>
      </form>

      <form method="POST" action="{{ route('admin.configuration.telegram.registration.create') }}" x-data>
        @csrf

        @if ($panel->unavailableReason())
          <x-button.secondary
            type="submit"
            size="small"
            aria-disabled="true"
            aria-describedby="telegram-unavailable"
            onclick="return false"
          >
            {{ __('Register with Telegram') }}
          </x-button.secondary>
        @else
          <x-button.secondary
            type="submit"
            size="small"
            @click.prevent="$dispatch('confirm-password', { form: $el.form })"
          >
            {{ __('Register with Telegram') }}
          </x-button.secondary>
        @endif
      </form>
    </div>
  </div>
</x-page-section>
