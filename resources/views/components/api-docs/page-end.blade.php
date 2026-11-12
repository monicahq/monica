{{-- The end of an API reference page: previous and next cards, where to get help, and the generation date. --}}
{{--
  @var \App\ViewModels\Docs\ApiDocsSection|null $previous
  @var \App\ViewModels\Docs\ApiDocsSection|null $next
  @var \Carbon\CarbonInterface $lastGenerated
  @var string|null $supportUrl
--}}
@props(['previous' => null, 'next' => null, 'lastGenerated', 'supportUrl' => null])

@php
  $card = 'flex flex-col gap-1 rounded-lg border border-hairline bg-canvas px-4 py-3 shadow-panel transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring';
  $link = 'underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring';
@endphp

<div class="mt-16 flex max-w-184 flex-col gap-8">
  @if ($previous || $next)
    <nav aria-label="Previous and next pages" class="grid grid-cols-2 gap-3 print:hidden">
      @if ($previous)
        <a href="{{ route('docs.api.show', ['section' => $previous->id]) }}" class="{{ $card }}">
          <span class="flex items-center gap-1.5 text-xs font-medium text-muted">
            <x-icon name="arrow-right" :size="14" class="rotate-180" />
            Previous
          </span>
          <span class="font-semibold text-ink">{{ $previous->title }}</span>
        </a>
      @endif

      @if ($next)
        <a href="{{ route('docs.api.show', ['section' => $next->id]) }}" class="{{ $card }} col-start-2 items-end text-right">
          <span class="flex items-center gap-1.5 text-xs font-medium text-muted">
            Next
            <x-icon name="arrow-right" :size="14" />
          </span>
          <span class="font-semibold text-ink">{{ $next->title }}</span>
        </a>
      @endif
    </nav>
  @endif

  <footer class="flex flex-col gap-6 border-t border-hairline pt-8">
    <ul class="flex flex-col gap-3 text-sm text-ink-soft">
      @if ($supportUrl)
        <li class="flex items-center gap-2.5">
          <x-icon name="mail" :size="16" class="text-muted" />
          <span>Stuck on something? <a href="{{ $supportUrl }}" class="{{ $link }}">Write to support</a>.</span>
        </li>
      @endif

      <li class="flex items-center gap-2.5">
        <x-icon name="clock" :size="16" class="text-muted" />
        <span>What changed recently? <a href="{{ route('docs.api.changelog.index') }}" class="{{ $link }}">Read the changelog</a>.</span>
      </li>

      <li class="flex items-center gap-2.5">
        <x-icon name="file" :size="16" class="text-muted" />
        <span>Reading this with an LLM? <a href="{{ route('llmsText.index') }}" class="{{ $link }}">Use llms.txt</a>.</span>
      </li>
    </ul>

    <p class="text-[12.5px] text-muted">Last generated on <time datetime="{{ $lastGenerated->toDateString() }}">{{ $lastGenerated->format('j F Y') }}</time></p>
  </footer>
</div>
