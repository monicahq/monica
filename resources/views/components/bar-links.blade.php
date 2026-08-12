{{--
  The two places in the top bar that take you out of the app: the documentation and
  the marketplace.

  Neither belongs to the vault you are in, which is why they are up here rather than
  in the dock, and why every signed in bar carries them. They sit on the right, last
  before the theme switch and the avatar, with a hairline between them and that pair:
  those two are about you, these two are about the product.

  Neither leads anywhere yet. Both screens are unwritten, and the anchors are drawn
  inert rather than left out because this is where they will be. They are anchors and
  not words because the bar has to hold the shape of what is coming, hover and focus
  included.

  The glyphs are the ones already in the set. The journal reads as something written
  down and read, which is what documentation is, and it is a long way from the dock's
  own journal button. The gift is a box somebody made and handed over, which is close
  enough to a marketplace.

  Below 900px the labels drop and the glyphs stay. The name is on the anchor rather
  than in the label, so it does not depend on the width of the window.
--}}
<div class="flex items-center gap-1">
  @foreach ([
    ['label' => __('Docs'), 'icon' => 'journal'],
    ['label' => __('Marketplace'), 'icon' => 'gift'],
  ] as $link)
    <a
      href="#"
      aria-label="{{ $link['label'] }}"
      class="flex h-9 items-center gap-1.75 rounded-md px-2.75 text-sm text-body transition-colors duration-(--duration-fast) ease-out hover:bg-sand hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
    >
      <x-icon :name="$link['icon']" :size="18" :stroke-width="1.5" />

      <span class="hidden min-[900px]:inline">{{ $link['label'] }}</span>
    </a>
  @endforeach

  <span aria-hidden="true" class="ml-1.5 h-5.5 w-px bg-hairline-strong"></span>
</div>
