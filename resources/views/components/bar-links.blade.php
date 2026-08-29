{{--
  The places in the top bar that take you out of the vault you are in: the
  documentation, the marketplace and your own settings.

  None of them belongs to that vault, which is why they are up here rather than in
  the dock, and why every signed in bar carries them. They sit on the right, last
  before the theme switch and the avatar, with a hairline between them and that pair.

  The marketplace leads to the catalogue, and the settings to your account, which is
  the one of the three that is about you rather than about the product. It is a
  worded link like the others rather than a glyph on its own beside the theme switch,
  where a gear and a sun read as one pair of switches.

  A screen with a navigation column asks for `settings="false"`, because the foot of
  that column already carries the way into the account behind the name of whoever is
  signed in, and the bar would be saying it twice.

  The documentation does not lead anywhere yet: that screen is unwritten, and its
  anchor is drawn inert rather than left out because this is where it will be. It is
  an anchor and not a word because the bar has to hold the shape of what is coming,
  hover and focus included.

  The glyphs are the ones already in the set. The journal reads as something written
  down and read, which is what documentation is, and it is a long way from the dock's
  own journal button. The gift is a box somebody made and handed over, which is close
  enough to a marketplace. The gear is the settings, as it is everywhere else.

  One more link appears for whoever runs the instance, and only for them. It is the
  one way into the admin space from the ordinary application: an administrator who
  had to remember an address would be an administrator who never opens the thing.
  Everybody else sees three links and no hint that a fourth exists, which is the
  whole of what the specification asks for when it says the area stays invisible to
  regular users.

  The glyph is the padlock, which already means "you cannot go in here" on the
  marketplace and on a share link that has gone. Here it means the opposite for the
  one person looking at it, and the same thing to everybody else.

  Below 900px the labels drop and the glyphs stay. The name is on the anchor rather
  than in the label, so it does not depend on the width of the window.

  @var bool $settings
--}}
@props([
  'settings' => true,
])

<div class="flex items-center gap-1">
  @foreach (array_filter([
    ['label' => __('Docs'), 'icon' => 'journal', 'href' => '#'],
    ['label' => __('Marketplace'), 'icon' => 'gift', 'href' => route('marketplace.index')],
    $settings
      ? ['label' => __('Account settings'), 'icon' => 'settings', 'href' => route('settings.index')]
      : null,
    auth()->user()?->isAdministrator()
      ? ['label' => __('Admin space'), 'icon' => 'lock', 'href' => route('admin.overview.index')]
      : null,
  ]) as $link)
    <a
      href="{{ $link['href'] }}"
      aria-label="{{ $link['label'] }}"
      class="flex h-9 items-center gap-1.75 rounded-md px-2.75 text-sm text-body transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none"
    >
      <x-icon :name="$link['icon']" :size="18" :stroke-width="1.5" />

      <span class="hidden min-[900px]:inline">{{ $link['label'] }}</span>
    </a>
  @endforeach

  <span aria-hidden="true" class="ml-1.5 h-5.5 w-px bg-hairline-strong"></span>
</div>
