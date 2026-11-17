{{-- The end of an API reference page: previous and next cards, where to get help, and the generation date. --}}
{{--
  @var \App\ViewModels\Docs\ApiDocsSection|null $previous
  @var \App\ViewModels\Docs\ApiDocsSection|null $next
  @var \Carbon\CarbonInterface $lastGenerated
  @var string|null $supportUrl
--}}
@props(['previous' => null, 'next' => null, 'lastGenerated', 'supportUrl' => null])

@php
  $link = 'underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring';
@endphp

<div class="mt-16 flex max-w-184 flex-col gap-8">
  <x-docs.page-navigation
    :previous="$previous ? ['url' => route('docs.api.show', ['section' => $previous->id]), 'label' => $previous->title] : null"
    :next="$next ? ['url' => route('docs.api.show', ['section' => $next->id]), 'label' => $next->title] : null"
  />

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
