{{-- One device signed in to the account, and the way to sign it out. --}}
{{-- @var array{id: string, device: string, address: ?string, lastUsedAt: string, isCurrent: bool} $session --}}
<x-row
  :title="$session['device']"
  :description="$session['address'] !== null
    ? __(':address · last used :used', ['address' => $session['address'], 'used' => $session['lastUsedAt']])
    : __('Last used :used', ['used' => $session['lastUsedAt']])"
  icon="device"
>
  <x-slot:end>
    @if ($session['isCurrent'])
      <x-badge tone="neutral">{{ __('This device') }}</x-badge>
    @else
      <x-delete-action
        :title="__('Sign out :device?', ['device' => $session['device']])"
        :confirm="__('Remove')"
        :action="route('settings.sessions.destroy', $session['id'])"
        :label="__('Remove')"
        size="small"
        :confirms-password="true"
      >
        <p>{{ __(':device is asked to sign in at its next request.', ['device' => $session['device']]) }}</p>

        <p>{{ __('Every device of your account, this one included, will ask you to sign in again once its session runs out.') }}</p>
      </x-delete-action>
    @endif
  </x-slot:end>
</x-row>
