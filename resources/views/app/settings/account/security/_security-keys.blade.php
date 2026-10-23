{{-- The security keys that answer the second step of signing in, and the way to add one. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<div id="security-keys">
  @if ($viewModel->passkeysAreAvailable())
    <div
      x-data="passkey({{ \Illuminate\Support\Js::from([
        'redirectUrl' => route('settings.security.index'),
        'routes' => [
          'options' => route('settings.securityKeyOptions.show'),
          'submit' => route('settings.securityKeys.create'),
        ],
        'text' => [
          'cancelled' => __('No security key was added. The prompt was dismissed.'),
          'alreadyRegistered' => __('This key already answers for your account.'),
          'failed' => __('The security key could not be added. Please try again.'),
          'tooMany' => __('Too many tries. Wait a minute and start again.'),
        ],
      ]) }})"
    >
      <x-row
        :title="__('Security keys')"
        :description="__('A key you touch instead of a code you type. Any key works, and so does the fingerprint reader or the screen lock of this device.')"
        icon="key"
      >
        <x-slot:end>
          <form x-data="{ asksForConfirmation: @js($viewModel->credentials()->offersPassword()) }" @submit.prevent="register()">
            <x-button
              size="small"
              type="button"
              x-show="supported"
              x-bind:disabled="busy"
              @click.prevent="asksForConfirmation ? $dispatch('confirm-password', { form: $el.form }) : register()"
            >{{ __('Add a security key') }}</x-button>
          </form>
        </x-slot:end>
      </x-row>

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
        class="border-b border-hairline px-4 py-2.5 text-sm text-body"
      >{{ __('This browser cannot add a security key. Try another browser.') }}</p>
    </div>
  @else
    <x-row
      :title="__('Security keys')"
      :description="__('Security keys need a secure connection. Reach this instance over https to add one.')"
      icon="key"
    />
  @endif

  @foreach ($viewModel->securityKeys() as $securityKey)
    @include('app.settings.account.security._security-key-row', [
      'securityKey' => $securityKey,
      'warning' => $viewModel->securityKeyRemovalWarning(),
      'asksForConfirmation' => $viewModel->credentials()->offersPassword(),
    ])
  @endforeach
</div>
