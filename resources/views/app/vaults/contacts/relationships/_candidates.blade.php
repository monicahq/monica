{{-- The people of this vault the other side of the relationship could be, narrowed as somebody types. --}}
{{-- @var \App\ViewModels\Vaults\RelationshipFormViewModel $viewModel --}}
<x-input
  type="search"
  id="query"
  :value="$viewModel->query()"
  :label="__('Search this vault')"
  :placeholder="__('Théo, mum, Laurent…')"
  x-model="query"
  x-on:input="forget()"
  x-on:input.debounce.400ms="search()"
  x-on:keydown.enter.prevent="search()"
  autocomplete="off"
/>

<div id="relationship-candidates" x-merge="replace" class="dim-when-busy max-h-70 overflow-y-auto overscroll-contain rounded-md border border-hairline">
  @forelse ($viewModel->candidates() as $candidate)
    <button
      type="button"
      x-on:click="choose({{ $candidate['id'] }}, @js($candidate['name']))"
      :aria-pressed="picked === '{{ $candidate['id'] }}' ? 'true' : 'false'"
      :class="picked === '{{ $candidate['id'] }}' ? 'bg-hover' : ''"
      class="focus-ring-inset flex w-full items-center gap-2.5 border-b border-hairline-soft px-3 py-2.25 text-start last:border-b-0 hover:bg-hover"
    >
      <x-contact-avatar :name="$candidate['name']" :size="26" />

      <span class="min-w-0 flex-1">
        <span class="block text-sm font-semibold text-ink">{{ $candidate['name'] }}</span>
        <span class="block text-xs text-muted">{{ $candidate['note'] }}</span>
      </span>

      <span class="text-xs font-semibold text-ink" x-show="picked === '{{ $candidate['id'] }}'" x-cloak>{{ __('Chosen') }}</span>
    </button>
  @empty
    <p class="px-3 py-3.5 text-sm text-muted">{{ $viewModel->noCandidatesLine() }}</p>
  @endforelse
</div>
