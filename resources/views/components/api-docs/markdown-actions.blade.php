{{-- The two ways to take a reference page as Markdown: copied for an LLM, or opened as a file. --}}
{{--
  @var string $markdown
  @var string $url
--}}
@props(['markdown', 'url'])

<div class="ms-auto flex flex-wrap gap-2">
  <button
    type="button"
    x-data="{ copied: false, markdown: {{ Js::from($markdown) }} }"
    @click="window.copyText(markdown).then((ok) => { copied = ok; setTimeout(() => (copied = false), 1600) })"
    class="cursor-pointer rounded-md border border-hairline bg-card px-3 py-1.75 font-sans text-sm font-medium text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
    aria-live="polite"
    x-text="copied ? 'Copied' : 'Copy for LLM'"
  >Copy for LLM</button>

  <a
    href="{{ $url }}"
    class="rounded-md border border-hairline bg-card px-3 py-1.75 text-sm font-medium whitespace-nowrap text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
  >View as Markdown</a>
</div>
