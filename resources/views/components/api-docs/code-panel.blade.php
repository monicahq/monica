{{--
  The request, ready to run, in whichever of the three languages the reader
  works in.

  The colouring is decided in the view model rather than here, so this only
  prints spans. The language tabs are real buttons in a named group, and the one
  in use says so with aria-pressed rather than only by being darker.

  @var \App\ViewModels\Docs\ApiDocsSection $section
--}}
@props(['section'])

@php
  $samples = $section->samples();
@endphp

<div
  x-data="{ copied: false, samples: {{ Js::from(collect($samples)->map(fn (array $sample): string => $sample['code'])) }} }"
  class="overflow-hidden rounded-lg border border-hairline bg-sunken"
>
  <div class="flex items-center gap-1.5 border-b border-hairline-soft bg-hover px-2.5 py-2">
    <x-api-docs.method-badge :method="$section->method" tinted />

    <div class="ml-1 flex gap-0.5" role="group" aria-label="Sample language">
      @foreach ($samples as $key => $sample)
        <button
          type="button"
          @click="lang = '{{ $key }}'"
          :aria-pressed="lang === '{{ $key }}' ? 'true' : 'false'"
          :class="lang === '{{ $key }}' ? 'bg-primary text-on-primary font-medium' : 'text-body hover:text-ink'"
          class="cursor-pointer rounded-xs px-2.5 py-1 text-[12px] whitespace-nowrap transition-colors duration-(--duration-fast) ease-out focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
        >{{ $sample['label'] }}</button>
      @endforeach
    </div>

    <button
      type="button"
      @click="window.copyText(samples[lang]).then((ok) => { copied = ok; setTimeout(() => (copied = false), 1600) })"
      class="ml-auto cursor-pointer rounded-xs px-2 py-1 text-[12px] text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
      aria-live="polite"
      x-text="copied ? 'Copied' : 'Copy'"
    >Copy</button>
  </div>

  @foreach ($samples as $key => $sample)
    <div x-show="lang === '{{ $key }}'" class="overflow-x-auto px-4 py-3.5">
      {{-- No whitespace inside the line: the box preserves it, so a newline or an
           indent in this template would be printed as part of the sample. --}}
      @foreach ($sample['lines'] as $line)
        <div class="font-mono text-[12.5px] leading-[1.75] whitespace-pre">@foreach ($line['parts'] as $part)<span class="{{ $part['colour'] === 'brand' ? 'text-ink' : 'text-muted' }}">{{ $part['text'] }}</span>@endforeach</div>
      @endforeach
    </div>
  @endforeach
</div>
