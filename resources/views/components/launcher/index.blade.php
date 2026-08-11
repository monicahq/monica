{{--
  The menu behind the vault chip: everything the dock does not carry, one keystroke
  away. ⌘K opens it from anywhere and esc leaves it, and leaving it never chooses
  anything.

  It only ever appears inside `<x-dock>`, whose `open` it reads. It is a separate file
  because it is a screen's worth of markup, not because it stands on its own.

  `x-trap` is what makes it a dialog rather than a panel that happens to be on top:
  the tab key stays inside it, the search field takes focus on open, the chip gets
  focus back on close, and `.inert` puts the rest of the page beyond the reach of both
  the pointer and a screen reader. `.noscroll` stops the page behind from moving.

  The search field is drawn and does nothing. There is no search in the application
  yet, and the field is where it will go: the alternative was a menu that has to be
  rebuilt around it later. It is the one inert control in here, and it is not the
  only route to anything.

  A tile with no url is a screen that has not been built. It is drawn and not
  offered, the same way the dock treats its own unbuilt destinations.

  Signing out is a form rather than a link, because it changes something.

  @var array<int, array{title: string, tiles: array<int, array{label: string, icon: string, iconClass: string, note: string|null, url: string|null, method: string|null}>}> $sections
--}}
@props([
  'sections' => [],
])

{{--
  The panel is centred by a layer of its own rather than by a translate on itself,
  so the transform is free for the transition that brings it in.

  Each run of tiles draws a rule above itself except the first, which already has
  one: the bottom edge of the search sitting over it.
--}}
<div x-cloak x-show="open" class="pointer-events-none fixed inset-x-0 bottom-[102px] z-51 flex justify-center px-3">
  <div
    x-show="open"
    x-trap.noscroll.inert="open"
    x-transition:enter="transition ease-out duration-[220ms]"
    x-transition:enter-start="translate-y-2.5 opacity-0"
    x-transition:leave="transition ease-out duration-[170ms]"
    x-transition:leave-end="translate-y-1 opacity-0"
    role="dialog"
    aria-modal="true"
    aria-label="{{ __('Menu') }}"
    class="pointer-events-auto max-h-[calc(100dvh-160px)] w-[min(680px,100%)] overflow-y-auto rounded-xl border border-hairline-strong bg-canvas shadow-pop"
  >
    <div class="flex items-center gap-2.5 border-b border-hairline bg-sand px-4.5 py-3.5">
      <x-icon name="search" :size="19" :stroke-width="1.6" class="text-muted" />

      <input
        type="search"
        placeholder="{{ __('Search people, notes, anything…') }}"
        aria-label="{{ __('Search people, notes, anything…') }}"
        class="min-w-0 flex-1 border-0 bg-transparent font-sans text-[16px] text-ink outline-none placeholder:text-placeholder"
      />

      <span class="rounded-xs border border-hairline-strong bg-canvas px-1.5 py-0.5 font-mono text-code text-muted" aria-hidden="true">esc</span>
    </div>

    @foreach ($sections as $section)
      <div @class(['px-4.5 py-3.5', 'border-t border-hairline' => ! $loop->first])>
        <p class="mb-2.5 uppercase text-xs tracking-caption text-muted">{{ $section['title'] }}</p>

        <div class="grid grid-cols-[repeat(auto-fill,minmax(148px,1fr))] gap-1.5">
          @foreach ($section['tiles'] as $tile)
            <x-launcher.tile :tile="$tile" />
          @endforeach
        </div>
      </div>
    @endforeach

    <div class="border-t border-hairline px-4.5 py-3.5">
      <p class="text-xs text-muted">
        {{ __('⌘K opens this from anywhere. Esc leaves it.') }}
      </p>
    </div>
  </div>
</div>
