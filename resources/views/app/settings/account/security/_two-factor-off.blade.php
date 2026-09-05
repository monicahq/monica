{{-- What somebody sees when a code is not asked for on top of their password. --}}
<x-row
  :title="__('Authenticator app')"
  :description="__('Ask us for a code from your authenticator app every time you sign in, on top of your password. Any app works.')"
  icon="device"
>
  <x-slot:end>
    <x-button size="small" :href="route('settings.twoFactor.new')" data-turbo="true">{{ __('Turn it on') }}</x-button>
  </x-slot:end>
</x-row>
