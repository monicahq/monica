{{--
  The passkeys on the account, and the way to add one.

  Adding a passkey is the browser's job once it starts, so the button hands over
  to the Alpine component and then gets out of the way. Everything the component
  has to say goes into the live region below it: a device prompt somebody cancels
  changes nothing on the page, so announcing it is the only way it is noticed.

  @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel
--}}
@php($passkeys = $viewModel->passkeys())

<div id="passkeys" x-merge="replace" class="transition-opacity [&[aria-busy]]:opacity-60">
  @if (! $viewModel->passkeysAreAvailable())
    @include('app.settings.account.security._passkeys-unavailable', [
      'reason' => $viewModel->passkeysUnavailableReason(),
    ])
  @else
    <div
      x-data="passkey({{ \Illuminate\Support\Js::from([
        'optionsUrl' => route('webauthn.store.options'),
        'storeUrl' => route('webauthn.store'),
        'redirectUrl' => route('settings.security.index'),
        'text' => [
          'defaultName' => __('This device'),
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
          <x-input
            x-ref="name"
            id="passkey_name"
            name="passkey_name"
            :label="__('Name this device')"
            :placeholder="__('This device')"
            size="small"
            class="w-48"
          />

          {{--
            Hidden rather than disabled when the browser cannot do passkeys. A
            disabled button is a dead control that explains nothing; the message
            below says why instead.
          --}}
          <x-button.secondary
            size="small"
            type="button"
            x-show="supported"
            x-bind:disabled="busy"
            @click="register()"
          >{{ __('Add a passkey') }}</x-button.secondary>
        </div>
      </div>

      {{--
        The live region stays in the DOM whether or not there is anything to say.
        A region that is added, or un-hidden, at the moment its text changes is
        not reliably announced: several screen readers only watch regions that
        were already there. So it is always present and simply empty.
      --}}
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
      <x-empty-state :title="__('No passkeys yet')" icon-class="bg-emerald-tint text-emerald">
        <x-slot:icon>
          <x-icon name="key" :size="30" :stroke-width="1.4" />
        </x-slot:icon>

        {{ __('A passkey lives on this device and is unlocked by your fingerprint, your face or your device PIN. There is nothing to type and nothing to remember.') }}
      </x-empty-state>
    @endforelse
  @endif
</div>
