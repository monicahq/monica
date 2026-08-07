{{--
  Inline help. A "?" badge next to a heading or a label that reveals a short
  explanation. Hovering previews it, clicking pins it open, and escape or a
  click elsewhere closes it again.

  The copy is given as the slot. There is no help library behind this yet, so
  every caller writes its own text.

  On a narrow screen the card is wider than the room left beside a badge that
  sits well into the row, so below sm it leaves the badge behind and settles at
  the foot of the screen instead, where it always fits and a thumb can reach it.
  The caret goes with it, having nothing left to point at.

  Usage: <x-help :title="__('Details')">What this box is for.</x-help>
  Pass align="right" when the badge sits near the right edge of its container.

  @var string|null $title
  @var string $align
--}}
@props([
  'title' => null,
  'align' => 'left',
])

<span
  x-data="{
    hovering: false,
    pinned: false,
    timer: null,
    canHover: false,
    init() { this.canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches },
    get open() { return this.hovering || this.pinned },
    enter() { if (! this.canHover) return; clearTimeout(this.timer); this.hovering = true },
    leave() { if (! this.canHover) return; clearTimeout(this.timer); this.timer = setTimeout(() => { this.hovering = false }, 130) },
    toggle() { this.pinned = ! this.pinned; this.hovering = false },
    close() { this.pinned = false; this.hovering = false },
  }"
  @mouseenter="enter()"
  @mouseleave="leave()"
  @mousedown.outside="close()"
  @keydown.escape.window="close()"
  {{ $attributes->class(['relative inline-flex align-middle leading-none']) }}
>
  <button
    type="button"
    @click="toggle()"
    :aria-expanded="open ? 'true' : 'false'"
    aria-haspopup="dialog"
    aria-label="{{ __('Help') }}"
    :class="open ? 'border-brand text-brand' : 'border-hairline-strong text-muted-soft'"
    class="flex size-4 cursor-pointer items-center justify-center rounded-full border bg-canvas text-xs leading-none font-bold transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-focus focus-visible:outline-none"
  >?</button>

  <div
    x-cloak
    x-show="open"
    x-transition
    role="dialog"
    @if ($title) aria-label="{{ $title }}" @endif
    class="z-70 text-left max-sm:fixed max-sm:inset-x-3 max-sm:top-auto max-sm:bottom-3 max-sm:w-auto max-sm:max-w-none sm:absolute sm:top-full sm:w-80 sm:max-w-[calc(100vw-2rem)] sm:pt-3 {{ $align === 'right' ? 'sm:-right-2' : 'sm:-left-2' }}"
  >
    {{-- The caret pointing back at the badge. --}}
    <div class="absolute top-1 size-3 rotate-45 border-t border-l border-hairline bg-canvas max-sm:hidden {{ $align === 'right' ? 'right-4' : 'left-4' }}" aria-hidden="true"></div>

    <div class="relative overflow-hidden rounded-xl border border-hairline bg-canvas shadow-xl">
      <div class="flex items-center gap-3 px-4 pt-4 pb-3">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-lg border border-hairline-strong bg-sunken text-sm font-bold text-brand" aria-hidden="true">?</span>

        @if ($title)
          <span class="min-w-0 flex-1 text-base leading-tight font-semibold tracking-tight text-ink">{{ $title }}</span>
        @endif

        <button
          type="button"
          x-show="pinned"
          @click="close()"
          aria-label="{{ __('Close') }}"
          class="flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted-soft transition-colors hover:text-ink"
        >
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <line x1="4" y1="4" x2="12" y2="12"></line>
            <line x1="12" y1="4" x2="4" y2="12"></line>
          </svg>
        </button>
      </div>

      <div class="px-4 pb-4 text-sm leading-relaxed text-muted">
        {{ $slot }}
      </div>
    </div>
  </div>
</span>
