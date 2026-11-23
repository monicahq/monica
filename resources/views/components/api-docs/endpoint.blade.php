{{-- One endpoint: what it does on the left, what to send and what comes back on the right. --}}
{{-- @var \App\ViewModels\Docs\ApiDocsSection $section --}}
@props(['section'])

@php
  $markdown = new \App\ViewModels\Docs\ApiDocsViewModel()->markdown($section);
@endphp

<article class="flex min-w-0 flex-wrap items-start gap-11">
  <div class="min-w-0 max-w-160 flex-1 basis-[420px]">
    <div class="mb-5 flex flex-wrap items-center gap-2">
      <span class="font-mono text-xs text-muted">v1</span>

      <x-api-docs.markdown-actions :markdown="$markdown" :url="route('docs.api.markdown.show', ['section' => $section->id])" />
    </div>

    <h1 class="mb-2.5 text-page-title text-ink">{{ $section->title }}</h1>

    <div class="mb-4 flex items-center gap-2 rounded-md border border-hairline bg-sunken px-3 py-2.25 font-mono text-sm">
      <x-api-docs.method-badge :method="$section->method" tinted />
      @if ($section->querySection !== null)
        <x-api-docs.method-badge :method="$section->querySection->method" tinted />
      @endif
      <span class="break-all text-ink">/api/v1{{ $section->path }}</span>
      @unless ($section->auth)
        <span class="ml-auto shrink-0 text-2xs text-muted">no token</span>
      @endunless
    </div>

    <p class="text-[14px] leading-[1.65] text-pretty text-body">{{ $section->description }}</p>

    @foreach ($section->body as $paragraph)
      <p class="mt-3.5 text-[14px] leading-[1.65] text-pretty text-body">{{ $paragraph }}</p>
    @endforeach

    @if ($section->querySection !== null)
      <p class="mt-3.5 text-[14px] leading-[1.65] text-pretty text-body">
        {{ $section->answersGetAndQuery() }}
        <a href="{{ route('docs.api.show', ['section' => 'query-method']) }}" class="underline decoration-hairline-strong underline-offset-3 hover:decoration-ink focus-ring">How QUERY works</a>.
      </p>
    @endif

    <x-api-docs.params :section="$section" />

    <x-api-docs.returns :section="$section" />

    <x-api-docs.status-codes :section="$section" />

    <x-api-docs.rate-limit :section="$section" />
  </div>

  <div class="min-w-0 max-w-135 flex-1 basis-[400px]">
    <div class="flex flex-col gap-4 lg:sticky lg:top-4">
      <x-api-docs.code-panel :section="$section" />
      @if ($section->querySection !== null)
        <x-api-docs.code-panel :section="$section->querySection" />
      @endif
      <x-api-docs.response-panel :section="$section" />

      <p class="text-xs leading-[1.6] text-muted">
        Keys act as you, with exactly the access you have. Make one per integration so you can revoke it alone.
      </p>
    </div>
  </div>
</article>
