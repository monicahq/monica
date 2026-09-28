{{-- The passkeys on the account, and the way to add one. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
@php($passkeys = $viewModel->passkeys())

<div id="passkeys" x-merge="replace" class="dim-when-busy">
  @if (! $viewModel->passkeysAreAvailable())
    @include('app.settings.account.security._passkeys-unavailable', [
      'reason' => $viewModel->passkeysUnavailableReason(),
    ])
  @else
    <div
      x-data="passkey({{ \Illuminate\Support\Js::from([
        'redirectUrl' => route('settings.security.index'),
        'text' => [
          'cancelled' => __('No passkey was added. The prompt was dismissed.'),
          'alreadyRegistered' => __('This device already has a passkey here.'),
          'failed' => __('The passkey could not be added. Please try again.'),
        ],
      ]) }})"
    >
      <div class="flex w-full flex-wrap items-center gap-3 border-b border-hairline px-4 py-3.5 text-left">
        <div class="min-w-0 flex-1">
          <p class="text-base font-semibold text-ink">{{ $passkeys->header() }}</p>
        </div>

        <div class="ml-auto flex shrink-0 flex-wrap items-center gap-2.5">
          <form x-data="{ asksForConfirmation: @js($viewModel->credentials()->offersPassword()) }" @submit.prevent="register()">
            <x-button
              size="small"
              type="button"
              x-show="supported"
              x-bind:disabled="busy"
              @click.prevent="asksForConfirmation ? $dispatch('confirm-password', { form: $el.form }) : register()"
            >{{ __('Add a passkey') }}</x-button>
          </form>
        </div>
      </div>

      <p
        x-text="message"
        role="status"
        aria-live="polite"
        class="px-4 text-sm empty:hidden"
        x-bind:class="failed ? 'text-error py-2.5 border-b border-hairline' : 'text-body py-2.5 border-b border-hairline'"
      ></p>

      <p
        x-cloak
        x-show="! supported"
        role="status"
        aria-live="polite"
        class="border-b border-hairline px-4 py-2.5 text-sm text-body"
      >{{ __('This browser cannot use passkeys. You can still sign in with your password or a link sent by email.') }}</p>
    </div>

    @forelse ($passkeys->passkeys() as $passkey)
      @include('app.settings.account.security._passkey-row', [
        'passkey' => $passkey,
        'lastOneOnAPasswordlessAccount' => $viewModel->usesPasswordlessSignIn() && count($passkeys->passkeys()) === 1,
      ])
    @empty
      <x-empty-state :title="__('No passkeys yet')" icon-class="bg-success-tint text-success">
        <x-slot:icon>
          <x-icon name="key" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('A passkey is kept on this device and is unlocked by your fingerprint, your face or your device PIN. There is nothing to type and nothing to remember.') }}
      </x-empty-state>
    @endforelse
  @endif
</div>
