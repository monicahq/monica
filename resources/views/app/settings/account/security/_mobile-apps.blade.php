{{-- The phones signed in through the Monica app, with the way to sign each one out. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<div id="mobile-apps">
  @forelse ($viewModel->mobileSessions() as $session)
    <x-row
      :title="$session['name']"
      :description="__('Signed in :signedIn, last used :lastUsed', ['signedIn' => $session['signedInAt'], 'lastUsed' => $session['lastUsedAt']])"
      icon="device"
    >
      <x-slot:end>
        <x-delete-action
          :title="__('Sign out :device?', ['device' => $session['name']])"
          :confirm="__('Sign out')"
          :action="route('settings.mobileSessions.destroy', $session['id'])"
          :label="__('Sign out')"
          size="small"
          :confirms-password="true"
        >
          <p>{{ __('The app on this phone is signed out at once. The other phones stay signed in.') }}</p>
        </x-delete-action>
      </x-slot:end>
    </x-row>
  @empty
    <x-empty-state :title="__('No phone signed in')">
      <x-slot:icon>
        <x-icon name="device" :size="30" :stroke-width="1.4" />
      </x-slot:icon>

      {{ __('Log in from the Monica app on your phone and it appears here, so you can sign it out if you lose it.') }}
    </x-empty-state>
  @endforelse
</div>
