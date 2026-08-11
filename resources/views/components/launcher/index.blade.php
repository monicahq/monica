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

  @var array<int, array{title: string, tiles: array<int, array{label: string, icon: string, hue: string, tint: string, note: string|null, url: string|null, method: string|null}>}> $sections
--}}
@props([
  'sections' => [],
])

<div x-cloak x-show="open" class="erica-launcher__layer">
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
    class="erica-launcher"
  >
    <div class="erica-launcher__search">
      <x-icon name="search" :size="19" :stroke-width="1.6" style="color: var(--muted)" />

      <input
        type="search"
        placeholder="{{ __('Search people, notes, anything…') }}"
        aria-label="{{ __('Search people, notes, anything…') }}"
      />

      <span class="erica-launcher__kbd" aria-hidden="true">esc</span>
    </div>

    @foreach ($sections as $section)
      <div class="erica-launcher__section">
        <p class="erica-launcher__title">{{ $section['title'] }}</p>

        <div class="erica-launcher__grid">
          @foreach ($section['tiles'] as $tile)
            <x-launcher.tile :tile="$tile" />
          @endforeach
        </div>
      </div>
    @endforeach

    <div class="erica-launcher__section">
      <p class="m-0" style="font: var(--text-caption); color: var(--muted)">
        {{ __('⌘K opens this from anywhere. Esc leaves it.') }}
      </p>
    </div>
  </div>
</div>
