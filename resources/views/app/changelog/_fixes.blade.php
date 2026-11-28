{{-- The fixes of one day of What’s new, in a compact list, each opening its own votes and comments. --}}
{{--
  @var list<array<string, mixed>> $fixes
  @var string $heading
  @var bool $feedback
--}}
<div class="flex max-w-160 flex-col gap-2.5">
  <h3><x-caption tone="muted">{{ $heading }}</x-caption></h3>

  <ul class="flex flex-col rounded-lg border border-hairline bg-card">
    @foreach ($fixes as $fix)
      <li x-data="{ expanded: false }" @class(['flex flex-col', 'border-t border-hairline' => ! $loop->first])>
        <button
          type="button"
          class="focus-ring-inset flex w-full cursor-pointer items-start gap-2.5 px-3.5 py-2.5 text-start"
          aria-expanded="false"
          x-bind:aria-expanded="expanded.toString()"
          aria-controls="changelog-fix-{{ $fix['id'] }}"
          @click="expanded = ! expanded"
        >
          <span class="mt-0.5 flex flex-none text-muted" aria-hidden="true"><x-icon name="check" :size="15" /></span>
          <span class="min-w-0 flex-1 text-base text-body text-pretty">
            {{ $fix['title'] }}
            @if ($fix['isNew'])
              <x-badge tone="accent" class="ms-1.5">{{ __('New') }}</x-badge>
            @endif
          </span>
          @if ($fix['area'] !== null)
            <span class="flex-none text-sm text-muted">{{ $fix['area'] }}</span>
          @endif
        </button>

        <div id="changelog-fix-{{ $fix['id'] }}" x-show="expanded" x-cloak class="flex flex-col gap-2.5 px-3.5 pb-3.5 ps-10">
          <div class="text-base text-body text-pretty [&_a]:underline [&_p+p]:mt-2">{!! $fix['bodyHtml'] !!}</div>

          @if ($fix['screenshots'] !== [])
            <x-screenshot-gallery :screenshots="$fix['screenshots']" :id="$fix['id']" :title="$fix['title']" :grid="true" />
          @endif

          @if ($feedback)
            @include('app.changelog._feedback', ['entry' => $fix])
          @endif
        </div>
      </li>
    @endforeach
  </ul>
</div>
