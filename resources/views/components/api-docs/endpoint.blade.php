{{--
  One endpoint: what it does on the left, what to send and what comes back on
  the right.

  The two columns wrap into one on a narrow screen, and the right one stops
  being sticky there, because a panel that follows the scroll on a phone covers
  the thing somebody is reading.

  @var \App\ViewModels\Docs\ApiDocsSection $section
--}}
@props(['section'])

@php
  $markdown = new \App\ViewModels\Docs\ApiDocsViewModel()->markdown($section);
@endphp

<article class="flex min-w-0 flex-wrap items-start gap-11">
  <div class="min-w-0 max-w-[640px] flex-1 basis-[420px]">
    <div class="mb-5 flex flex-wrap items-center gap-2">
      <span class="font-mono text-[12px] text-brand">v1</span>

      <div class="ml-auto flex flex-wrap gap-2">
        <button
          type="button"
          x-data="{ copied: false, markdown: {{ Js::from($markdown) }} }"
          @click="window.copyText(markdown).then((ok) => { copied = ok; setTimeout(() => (copied = false), 1600) })"
          class="cursor-pointer rounded-md border border-hairline-strong bg-sunken px-3 py-1.75 font-sans text-[13px] font-medium text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
          aria-live="polite"
          x-text="copied ? 'Copied' : 'Copy for LLM'"
        >Copy for LLM</button>

        <a
          href="{{ route('docs.api.markdown.show', ['section' => $section->id]) }}"
          class="rounded-md border border-hairline-strong bg-sunken px-3 py-1.75 text-[13px] font-medium whitespace-nowrap text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
        >View as Markdown</a>
      </div>
    </div>

    <h1 class="mb-2.5 text-[24px] font-semibold tracking-[-0.025em] text-ink">{{ $section->title }}</h1>

    <div class="mb-4 flex items-center gap-2 rounded-md border border-hairline bg-sunken px-3 py-2.25 font-mono text-[13px]">
      <x-api-docs.method-badge :method="$section->method" />
      <span class="break-all text-ink">/api/v1{{ $section->path }}</span>
      @unless ($section->auth)
        <span class="ml-auto shrink-0 text-[11px] text-muted">no token</span>
      @endunless
    </div>

    <p class="text-[14px] leading-[1.65] text-pretty text-body">{{ $section->description }}</p>

    @foreach ($section->body as $paragraph)
      <p class="mt-3.5 text-[14px] leading-[1.65] text-pretty text-body">{{ $paragraph }}</p>
    @endforeach

    <x-api-docs.params :section="$section" />

    @if ($section->returns)
      <h2 class="mt-9 mb-1 text-[18px] font-semibold tracking-[-0.015em] text-ink">Returns</h2>
      <p class="text-[14px] leading-[1.65] text-pretty text-body">{{ $section->returns }}</p>
    @endif

    <x-api-docs.rate-limit :section="$section" />
  </div>

  <div class="min-w-0 max-w-[540px] flex-1 basis-[400px]">
    <div class="flex flex-col gap-4 lg:sticky lg:top-31">
      <x-api-docs.code-panel :section="$section" />
      <x-api-docs.response-panel :section="$section" />

      <p class="text-[12px] leading-[1.6] text-muted">
        Keys act as you, with exactly the access you have. Make one per integration so you can revoke it alone.
      </p>
    </div>
  </div>
</article>
