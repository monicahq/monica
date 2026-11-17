{{-- The bar that says which reference this is, and lets somebody search it. --}}
<div class="print:hidden border-b border-hairline bg-canvas">
  <div class="mx-auto flex h-14 max-w-390 items-center gap-5 px-4 sm:px-7">
    <a href="{{ route('docs.api.index') }}" class="flex shrink-0 items-center gap-2.25 text-ink rounded-md focus-ring">
      <x-icon name="key" :size="16" class="text-muted" />
      <span class="text-card-title">API</span>
      <span class="rounded-xs border border-hairline-soft bg-sunken px-1.5 py-0.5 font-mono text-2xs text-body">v1</span>
    </a>

    <label class="hidden h-9 max-w-105 flex-1 items-center gap-2.25 rounded-md border border-hairline bg-card px-2.75 transition-colors duration-(--duration-fast) ease-out focus-within:border-hairline-strong md:flex">
      <span class="sr-only">Search endpoints</span>
      <x-icon name="search" :size="15" class="shrink-0 text-muted" />
      <input
        type="search"
        x-model="query"
        x-ref="search"
        @keydown.window.prevent.meta.k="$refs.search.focus()"
        @keydown.window.prevent.ctrl.k="$refs.search.focus()"
        placeholder="Search endpoints"
        class="w-full min-w-0 border-0 bg-transparent p-0 font-sans text-[14px] text-ink placeholder-muted-soft focus:outline-none"
      />
      <kbd class="rounded-xs border border-hairline px-1.25 py-px font-mono text-2xs text-muted-soft">⌘K</kbd>
    </label>

    <div class="ml-auto flex shrink-0 items-center gap-2.5">
      <a
        href="{{ route('docs.api.openapi') }}"
        class="rounded-md border border-hairline px-3.5 py-2 text-sm font-medium text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
        target="_blank"
      >OpenAPI Spec</a>

      <a
        href="{{ route('settings.security.index') }}"
        class="rounded-md border border-hairline px-3.5 py-2 text-sm font-medium text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-ring"
      >Get an API key</a>
    </div>
  </div>
</div>
