{{--
  The light and dark switch. It writes to the Alpine theme store, which puts the
  class on <html> and remembers the choice.
--}}
@php
  /* Bigger on a phone, where the two buttons are aimed at with a thumb. */
  $button = 'flex h-6 w-7 cursor-pointer items-center justify-center rounded-sm transition-colors max-sm:h-8 max-sm:w-9';
@endphp

<div x-data {{ $attributes->class(['flex gap-0.5 rounded-md border border-hairline-strong bg-canvas p-0.5']) }}>
  <button
    type="button"
    @click="$store.theme.set(false)"
    :class="$store.theme.dark ? 'bg-transparent text-muted-soft' : 'bg-hover text-ink'"
    class="{{ $button }}"
    :aria-pressed="$store.theme.dark ? 'false' : 'true'"
    aria-label="{{ __('Light theme') }}"
  >
    <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
      <circle cx="8" cy="8" r="3"></circle>
      <path d="M8 1.4v1.8M8 12.8v1.8M1.4 8h1.8M12.8 8h1.8M3.4 3.4l1.3 1.3M11.3 11.3l1.3 1.3M12.6 3.4l-1.3 1.3M4.7 11.3l-1.3 1.3"></path>
    </svg>
  </button>

  <button
    type="button"
    @click="$store.theme.set(true)"
    :class="$store.theme.dark ? 'bg-hover text-ink' : 'bg-transparent text-muted-soft'"
    class="{{ $button }}"
    :aria-pressed="$store.theme.dark ? 'true' : 'false'"
    aria-label="{{ __('Dark theme') }}"
  >
    <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
      <path d="M12.6 9.7A5.1 5.1 0 0 1 6.3 3.4a5.1 5.1 0 1 0 6.3 6.3Z"></path>
    </svg>
  </button>
</div>
