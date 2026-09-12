{{-- What comes back, and with which status. --}}
{{-- @var \App\ViewModels\Docs\ApiDocsSection $section --}}
@props(['section'])

@php
  $lines = $section->responseLines();
  $tone = $section->responseStatus >= 400 ? 'text-error' : 'text-success';

  $body = collect($lines)
    ->map(fn (array $line): string => collect($line['parts'])->pluck('text')->implode(''))
    ->implode(PHP_EOL);
@endphp

<div class="overflow-hidden rounded-lg border border-hairline bg-sunken">
  <div class="flex items-center gap-2 border-b border-hairline-soft bg-hover px-3 py-2.25">
    <span class="text-[13px] font-semibold text-ink">Response</span>
    <span class="font-mono text-[12px] {{ $tone }}">{{ $section->responseStatus }}</span>

    @if ($lines !== [])
      <button
        type="button"
        x-data="{ copied: false, body: {{ Js::from($body) }} }"
        @click="window.copyText(body).then((ok) => { copied = ok; setTimeout(() => (copied = false), 1600) })"
        class="ml-auto cursor-pointer rounded-xs px-2 py-1 text-[12px] text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring"
        aria-live="polite"
        x-text="copied ? 'Copied' : 'Copy'"
      >Copy</button>
    @endif
  </div>

  <div class="overflow-x-auto px-4 py-3.5">
    @if ($lines === [])
      <p class="font-mono text-[12.5px] text-muted">No content.</p>
    @else
      @foreach ($lines as $line)
        <div class="font-mono text-[12.5px] leading-[1.75] whitespace-pre">@foreach ($line['parts'] as $part)<span class="{{ $part['colour'] === 'brand' ? 'text-ink' : 'text-muted' }}">{{ $part['text'] }}</span>@endforeach</div>
      @endforeach
    @endif
  </div>
</div>
