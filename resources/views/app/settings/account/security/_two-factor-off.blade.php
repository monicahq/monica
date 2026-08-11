{{--
  What somebody sees when a code is not asked for on top of their password.

  The screen it leads to is built on this same layout, so it is handed to turbo
  and only the body changes hands.
--}}
<x-row
  :title="__('Authenticator app')"
  :description="__('Ask us for a code from your authenticator app every time you sign in, on top of your password. Any app works.')"
  icon="device"
  icon-color="var(--muted)"
  icon-background="var(--sand)"
>
  <x-slot:end>
    <x-button size="small" :href="route('settings.twoFactor.new')" data-turbo="true">{{ __('Turn it on') }}</x-button>
  </x-slot:end>
</x-row>
