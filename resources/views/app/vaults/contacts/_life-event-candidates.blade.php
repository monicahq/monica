{{-- The people of the vault who could be added to a life event, narrowed as somebody types. --}}
{{-- @var array<int, array{id: int, name: string}> $candidates --}}
<div id="life-event-candidates" x-merge="replace" aria-live="polite" class="dim-when-busy max-h-70 overflow-y-auto overscroll-contain rounded-lg border border-hairline bg-canvas shadow-pop">
  @forelse ($candidates as $candidate)
    <button
      type="button"
      x-on:click="choose({{ $candidate['id'] }}, @js($candidate['name']))"
      class="focus-ring-inset flex w-full items-center gap-2.5 border-b border-hairline px-3 py-2 text-left last:border-b-0 hover:bg-hover"
    >
      <x-contact-avatar :name="$candidate['name']" :size="24" />
      <span class="min-w-0 flex-1 text-sm text-ink">{{ $candidate['name'] }}</span>
      <span class="text-muted" aria-hidden="true"><x-icon name="plus" :size="15" /></span>
    </button>
  @empty
    <p class="px-3 py-2.5 text-sm text-muted">{{ __('Nobody else matches.') }}</p>
  @endforelse
</div>
