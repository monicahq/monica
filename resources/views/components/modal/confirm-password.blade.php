{{-- The card that asks for a credential before a change to the way somebody signs in. --}}
{{--
  @var bool $confirmed
  @var int $secondsLeft
  @var bool $offersPassword
  @var bool $offersPasskey
--}}
@props([
  'confirmed' => false,
  'secondsLeft' => 0,
  'offersPassword' => false,
  'offersPasskey' => false,
])

<div
  x-data="confirmPassword({{ \Illuminate\Support\Js::from([
    'confirmed' => $confirmed,
    'secondsLeft' => $secondsLeft,
    'url' => route('auth.confirmPassword.create'),
    'text' => [
      'wrong' => __('That is not the right password.'),
      'tooMany' => __('Too many tries. Wait a minute and start again.'),
      'failed' => __('That did not work. Try again.'),
    ],
  ]) }})"
  @confirm-password.window="start($event.detail.form)"
  @keydown.escape.window="close()"
  class="contents"
>
  <div x-cloak x-show="open" class="fixed print:hidden inset-0 z-70 flex items-end justify-center p-4 sm:items-center">
    <div x-show="open" x-transition.opacity.duration.170ms @click="close()" class="absolute inset-0 bg-scrim" aria-hidden="true"></div>

    <div
      x-show="open"
      x-trap.noscroll.inert="open"
      x-transition:enter="transition ease-out duration-(--duration-slow)"
      x-transition:enter-start="translate-y-2 opacity-0"
      x-transition:leave="transition ease-out duration-(--duration-standard)"
      x-transition:leave-end="opacity-0"
      role="dialog"
      aria-modal="true"
      aria-label="{{ __('Confirm it is you') }}"
      class="relative w-full max-w-md overflow-hidden rounded-lg border border-hairline bg-canvas shadow-pop"
    >
      <div class="flex gap-3.5 p-5">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-sm bg-warning-tint text-warning" aria-hidden="true">
          <x-icon name="lock" :size="19" :stroke-width="1.6" />
        </span>

        <div class="flex min-w-0 flex-col gap-3">
          <p class="text-card-title text-ink">{{ __('Confirm it is you') }}</p>

          <p class="text-sm leading-relaxed text-body">{{ __('What you are about to change decides how you sign in, so we ask for a credential first. You will not be asked again for a little while.') }}</p>

          @if ($offersPassword)
            <x-input
              type="password"
              id="confirm_password"
              name="confirm_password"
              :label="__('Your password')"
              autocomplete="current-password"
              x-model="password"
              @keydown.enter.prevent="withPassword()"
            />
          @endif

          @if (! $offersPassword && ! $offersPasskey)
            <p class="text-sm leading-relaxed text-error">{{ __('This account holds neither a password nor a passkey, so there is nothing to confirm with.') }}</p>
          @endif

          <p
            x-text="message"
            role="status"
            aria-live="polite"
            class="text-sm empty:hidden"
            x-bind:class="failed ? 'text-error' : 'text-body'"
          ></p>
        </div>
      </div>

      <div class="flex flex-col-reverse gap-3 border-t border-hairline bg-sunken px-5 py-3.5 sm:flex-row sm:justify-end">
        <x-button.secondary size="small" type="button" @click="close()">{{ __('Cancel') }}</x-button.secondary>

        @if ($offersPasskey)
          <div
            x-data="passkey({{ \Illuminate\Support\Js::from([
              'routes' => [
                'options' => route('passkey.confirm-options'),
                'submit' => route('passkey.confirm'),
              ],
              'redirectUrl' => null,
              'text' => [
                'cancelled' => __('Nothing was confirmed. The prompt was dismissed.'),
                'alreadyRegistered' => __('That passkey cannot be used here.'),
                'failed' => __('That did not work. Try again, or use your password.'),
              ],
            ]) }})"
            @passkey-confirmed="submit()"
            class="contents"
          >
            <x-button.secondary
              size="small"
              type="button"
              x-cloak
              x-show="supported"
              x-bind:disabled="busy"
              @click="confirm()"
            >{{ __('Use a passkey') }}</x-button.secondary>
          </div>
        @endif

        @if ($offersPassword)
          <x-button size="small" type="button" x-bind:disabled="busy" @click="withPassword()">{{ __('Confirm') }}</x-button>
        @endif
      </div>
    </div>
  </div>
</div>
