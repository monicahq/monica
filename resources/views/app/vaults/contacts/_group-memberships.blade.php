{{-- The groups somebody belongs to today, then the ones they left. --}}
{{-- @var \App\ViewModels\Vaults\ContactGroupsBlock $groups --}}
@php
  $memberships = $groups->memberships();
@endphp
<x-panel :title="__('Groups')" icon="people" :flush="true">
  <x-slot:action>
    <x-button.secondary :href="$groups->groupsUrl()" data-turbo="true" size="small">{{ __('Add to a group') }}</x-button.secondary>
  </x-slot:action>

  @if ($memberships === [])
    <p class="p-4 text-sm text-pretty text-muted">{{ $groups->emptyLine() }}</p>
  @else
    <ul class="list-none">
      @foreach ($memberships as $membership)
        <li class="border-b border-hairline last:border-b-0">
          <a href="{{ $membership['url'] }}" data-turbo="true" class="flex items-center gap-2.5 px-4 py-2 no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset">
            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm font-semibold text-ink">{{ $membership['name'] }}</span>

              @if ($membership['role'] !== null || $membership['period'] !== null)
                <span class="block truncate text-xs text-muted">{{ collect([$membership['role'], $membership['period']])->filter()->implode(' · ') }}</span>
              @endif
            </span>

            @unless ($membership['isCurrent'])
              <x-badge>{{ __('Left') }}</x-badge>
            @endunless
          </a>
        </li>
      @endforeach
    </ul>
  @endif
</x-panel>
