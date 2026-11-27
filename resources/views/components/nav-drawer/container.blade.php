{{-- What a column of links sits in: a drawer on a narrow window, holding focus while it is open. --}}
{{--
  @var string $tag
  @var bool $productLink
--}}
@props([
  'tag' => 'div',
  'productLink' => true,
])

<{{ $tag }}
  id="main-nav"
  x-trap.noscroll.inert="navOpen"
  @resize.window.debounce="if (window.matchMedia('(min-width: 64rem)').matches) { navOpen = false }"
  :class="navOpen && 'max-lg:visible! max-lg:translate-x-0! max-lg:transition-[translate]!'"
  class="fixed print:hidden inset-y-0 left-0 z-40 flex w-62 flex-col border-r border-hairline bg-card transition-[translate,visibility] duration-(--duration-slow) ease-out max-lg:invisible max-lg:-translate-x-full lg:static lg:h-full lg:w-auto lg:translate-x-0"
>
  <div @class(['flex items-center gap-2.5 px-4 pt-3.5 pb-2.5', 'lg:hidden' => ! $productLink])>
    @if ($productLink)
      <x-product-link />
    @endif

    <button
      type="button"
      @click="navOpen = false"
      aria-label="{{ __('Close the menu') }}"
      class="-mr-1 ml-auto flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted transition-colors duration-(--duration-fast) ease-out hover:bg-hover hover:text-ink focus-ring lg:hidden"
    >
      <x-icon name="close" :size="15" :stroke-width="1.5" />
    </button>
  </div>

  {{ $slot }}
</{{ $tag }}>
