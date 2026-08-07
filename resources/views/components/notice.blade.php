{{--
  A quiet aside: something worth reading once, that is neither an error nor a
  confirmation. The dashed border is what tells it apart from a box.
--}}
<div {{ $attributes->class(['flex items-start gap-3 rounded-lg border border-dashed border-hairline-strong bg-sunken px-4 py-3.5']) }}>
  <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" class="mt-0.5 shrink-0 text-muted" aria-hidden="true">
    <circle cx="8" cy="8" r="6"></circle>
    <line x1="8" y1="7.2" x2="8" y2="11.4"></line>
    <circle cx="8" cy="5" r="0.5" fill="currentColor"></circle>
  </svg>

  <div class="text-xs leading-relaxed text-body">{{ $slot }}</div>
</div>
