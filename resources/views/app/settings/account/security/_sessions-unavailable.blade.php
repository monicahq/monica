{{-- Why the devices signed in to the account cannot be listed on this instance. --}}
<div class="px-4 py-3.5">
  <x-notice tone="quiet">
    {{ __('This instance keeps sessions where they cannot be listed. Signing out still ends the session on this device.') }}
  </x-notice>
</div>
