{{-- The possessions somebody has had or lent, each leading to the loans of that possession. --}}
{{-- @var \App\ViewModels\Vaults\ContactLoansBlock $loans --}}
<x-panel :title="__('Loans')" icon="relationship" :flush="true">
  <ul class="list-none">
    @foreach ($loans->loans() as $loan)
      <li class="border-b border-hairline last:border-b-0">
        <a href="{{ $loan['url'] }}" class="flex items-center gap-2.5 px-4 py-2 no-underline transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring-inset">
          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold text-ink">{{ $loan['possession'] }}</span>
            <span class="block truncate text-xs text-muted">{{ $loan['verb'] }} · {{ $loan['period'] }}</span>
          </span>
          <x-badge :tone="$loan['tone']">{{ $loan['status'] }}</x-badge>
        </a>
      </li>
    @endforeach
  </ul>
</x-panel>
