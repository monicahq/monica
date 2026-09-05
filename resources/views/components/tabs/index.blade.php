{{-- A row of tabs sitting on a rule that runs the whole width. --}}
<div role="tablist" {{ $attributes->class(['flex flex-wrap items-end gap-0.5 border-b border-hairline']) }}>
  {{ $slot }}
</div>
