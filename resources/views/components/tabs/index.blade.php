{{-- A row of tabs sitting on a rule that runs the whole width. --}}
<div role="tablist" {{ $attributes->class(['flex items-end gap-0.5 overflow-x-auto [scrollbar-width:none] shadow-[inset_0_-1px_0_var(--color-hairline)]']) }}>
  {{ $slot }}
</div>
