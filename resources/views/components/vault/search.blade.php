{{--
  The way into the launcher from the top bar.

  The search does not search yet. It opens the launcher, which is where searching will
  live and where the same field already sits, so this is a second door onto one room
  rather than a control of its own that leads nowhere. The shortcut it shows is real:
  <x-dock> listens for the event on the window, and binds ⌘K to the same panel.

  It is drawn as a field because that is what it is a way into, so it takes the inset
  shade of a well rather than the raised edge of a button, and it sits on sand rather
  than on the white of the bar. Beside <x-button.bar> that makes the pair one material
  at two elevations.

  It is gone below 900px, where the bar has room for the vault and where it is rather
  than for a third thing. Nothing is only reachable through it.
--}}
<button
  type="button"
  @click="$dispatch('launcher-open')"
  {{
    $attributes->class([
      'hidden h-9 min-w-65 cursor-pointer items-center gap-2.25 rounded-md border border-hairline-strong',
      'bg-sand px-3 text-left text-sm text-muted shadow-press-inset',
      'transition-[background-color] duration-(--duration-fast) ease-out hover:bg-sand-deep',
      'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring min-[900px]:flex',
    ])
  }}
>
  <x-icon name="search" :size="17" :stroke-width="1.6" />

  {{ __('Search people, notes, anything…') }}

  <span class="ml-auto rounded-xs border border-hairline px-1.25 py-px font-mono text-[11px]" aria-hidden="true">⌘K</span>
</button>
