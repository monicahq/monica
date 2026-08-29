{{--
  The bar that says which reference this is, and lets somebody search it.

  The search box carries a real label rather than only a placeholder, which
  disappears the moment anybody types into it and was never read out to begin
  with.

  The version beside the name is not decoration. It is in the URL of every
  endpoint below.
--}}
<div class="sticky top-14 z-4 border-b border-hairline bg-canvas">
  <div class="mx-auto flex h-14 max-w-[97.5rem] items-center gap-5 px-4 sm:px-7">
    <a href="{{ route('docs.api.index') }}" class="flex shrink-0 items-center gap-2.25 text-ink rounded-md focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none">
      <x-icon name="key" :size="16" class="text-muted" />
      <span class="text-[15px] font-semibold tracking-[-0.015em]">API</span>
      <span class="rounded-xs border border-hairline-soft bg-sunken px-1.5 py-0.5 font-mono text-[11px] text-body">v1</span>
    </a>

    <label class="hidden h-9 max-w-[26.25rem] flex-1 items-center gap-2.25 rounded-md border border-hairline bg-card px-2.75 transition-colors duration-(--duration-fast) ease-out focus-within:border-hairline-strong md:flex">
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
      <kbd class="rounded-xs border border-hairline px-1.25 py-px font-mono text-[11px] text-muted-soft">⌘K</kbd>
    </label>

    <a
      href="{{ route('settings.security.index') }}"
      class="ml-auto shrink-0 rounded-md border border-hairline px-3.5 py-2 text-sm font-medium text-ink transition-colors duration-(--duration-fast) ease-out hover:bg-hover focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
    >Get an API key</a>
  </div>
</div>
