{{-- The profiles that name somebody, such as the animal they look after, each leading to its page. --}}
{{-- @var \App\ViewModels\Vaults\ContactProfilesBlock $profiles --}}
<x-panel :title="__('Profiles')" icon="pet" :flush="true">
  <ul role="list" class="list-none">
    @foreach ($profiles->profiles() as $profile)
      <li class="border-b border-hairline last:border-b-0">
        <a href="{{ $profile['url'] }}" data-turbo="true" class="flex items-center gap-2.5 px-4 py-2 no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset">
          <x-entity-icon :name="$profile['icon']" :size="16" class="text-muted" />

          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold text-ink">{{ $profile['name'] }}</span>
            <span class="block text-xs text-pretty text-muted">{{ $profile['type'] }} · {{ $profile['detail'] }}</span>
          </span>
        </a>
      </li>
    @endforeach
  </ul>
</x-panel>
