{{-- The records a picker offers for what was typed, each one a button that chooses it. --}}
{{--
  @var string $id
  @var list<array{uuid: string, name: string}> $candidates
  @var string|null $emptyLine
--}}
@props([
  'id',
  'candidates' => [],
  'emptyLine' => null,
])

<div id="{{ $id }}" x-merge="update" aria-live="polite" class="dim-when-busy">
  @if ($candidates !== [])
    <div class="max-h-70 overflow-y-auto overscroll-contain rounded-md border border-hairline">
      @foreach ($candidates as $candidate)
        <button
          type="button"
          x-on:click="choose(@js($candidate['uuid']), @js($candidate['name'])); $nextTick(() => $refs.change?.focus())"
          :aria-pressed="chosen === @js($candidate['uuid']) ? 'true' : 'false'"
          class="focus-ring-inset flex w-full items-center gap-2.5 border-b border-hairline-soft px-3 py-2.25 text-start last:border-b-0 hover:bg-hover"
        >
          <x-contact-avatar :name="$candidate['name']" :size="26" />

          <span class="min-w-0 flex-1 truncate text-sm font-semibold text-ink">{{ $candidate['name'] }}</span>
        </button>
      @endforeach
    </div>
  @elseif ($emptyLine !== null)
    <p class="rounded-md border border-hairline px-3 py-3.5 text-sm text-muted">{{ $emptyLine }}</p>
  @endif

  {{ $slot }}
</div>
