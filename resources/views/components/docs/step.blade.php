{{-- One numbered step inside x-docs.steps: the number in a circle, a line down to the next circle, and the content. --}}
<li {{ $attributes->class('group relative flex items-start gap-4 rounded-lg p-3 transition-colors duration-150 ease-out hover:bg-card [counter-increment:step]') }}>
  <span aria-hidden="true" class="absolute top-11 -bottom-3 left-6.75 z-1 w-px bg-hairline-strong transition-colors duration-150 ease-out group-hover:bg-ink in-[li:last-child]:hidden"></span>

  <span aria-hidden="true" class="relative z-2 flex size-8 shrink-0 items-center justify-center rounded-full border border-hairline-strong bg-canvas text-[13px] font-semibold text-ink shadow-panel transition-all duration-150 ease-out after:content-[counter(step)] group-hover:border-primary group-hover:bg-primary group-hover:text-on-primary group-hover:shadow-[0_0_0_4px_var(--hairline)]"></span>

  <div class="flex min-w-0 flex-1 flex-col gap-2 pt-1 transition-colors duration-150 ease-out group-hover:[&_p]:text-ink">
    {{ $slot }}
  </div>
</li>
