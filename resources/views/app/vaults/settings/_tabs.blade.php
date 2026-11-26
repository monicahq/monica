{{-- The two faces of the settings of a vault: what it is, and what was deleted from it. --}}
{{--
  @var \App\Models\Vault $vault
  @var string $current
  @var int $trashCount
--}}
<x-tabs :aria-label="__('Vault settings')">
  <x-tabs.item :href="route('vaults.settings.index', ['vault' => $vault])" :selected="$current === 'general'" icon="vault" data-turbo="true">
    {{ __('General') }}
  </x-tabs.item>

  <x-tabs.item
    :href="route('vaults.trash.index', ['vault' => $vault])"
    :selected="$current === 'trash'"
    icon="close"
    :count="$trashCount === 0 ? null : $trashCount"
    :count-label="trans_choice(':count item in the trash|:count items in the trash', $trashCount)"
    data-turbo="true"
  >
    {{ __('Trash') }}
  </x-tabs.item>
</x-tabs>
