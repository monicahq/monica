{{-- The apps connected through OAuth, phones and assistants alike, with the way to disconnect each. --}}
{{-- @var \App\ViewModels\Settings\Account\Security\SecurityViewModel $viewModel --}}
<div id="connected-apps">
  @forelse ($viewModel->connections() as $connection)
    <x-row
      :title="$connection['name']"
      :description="$connection['description']"
      icon="link"
    >
      <x-slot:end>
        <x-delete-action
          :title="__('Disconnect :name?', ['name' => $connection['name']])"
          :confirm="__('Disconnect')"
          :action="route('settings.oauthConnections.destroy', $connection['id'])"
          :label="__('Disconnect')"
          size="small"
          :confirms-password="true"
        >
          <p>{{ __('It loses access at once, and has to ask you again to come back. Your other connections stay as they are.') }}</p>
        </x-delete-action>
      </x-slot:end>
    </x-row>
  @empty
    <x-empty-state :title="__('No app connected')">
      <x-slot:icon>
        <x-icon name="link" :size="30" :stroke-width="1.4" />
      </x-slot:icon>

      {{ $viewModel->connectionsEmptyState() }}
    </x-empty-state>
  @endforelse
</div>
