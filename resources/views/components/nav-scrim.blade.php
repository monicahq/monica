{{-- The dimmed page behind the open navigation drawer, which closes the drawer when pressed. --}}
<div
  x-cloak
  x-show="navOpen"
  x-transition.opacity.duration.150ms
  @click="navOpen = false"
  class="fixed print:hidden inset-0 z-30 bg-scrim lg:hidden"
  aria-hidden="true"
></div>
