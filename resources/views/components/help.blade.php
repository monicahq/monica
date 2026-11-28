{{-- A question mark beside something unclear, opening a short explanation and the way to its guide. --}}
{{--
  @var string $label
  @var string $title
  @var string $guide
  @var string $align
  @var \Illuminate\View\ComponentSlot|null $example
--}}
@props([
  'label',
  'title',
  'guide',
  'align' => 'left',
])

@php
  [$guideId, $guideSection] = array_pad(explode('#', $guide, 2), 2, null);
  $guidePage = \App\ViewModels\Docs\PortalCatalogue::pageForReader($guideId);
  $guideUrl = $guidePage === null ? null : $guidePage->url().($guideSection ? "#{$guideSection}" : '');
@endphp

<span
  x-data="{
    hovering: false,
    pinned: false,
    timer: null,
    canHover: false,
    init() { this.canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches },
    get open() { return this.hovering || this.pinned },
    announce() { this.$dispatch('help-opened', { id: this.$id('help') }) },
    enter() { if (! this.canHover) return; clearTimeout(this.timer); if (! this.open) this.announce(); this.hovering = true },
    leave() { if (! this.canHover) return; clearTimeout(this.timer); this.timer = setTimeout(() => { this.hovering = false }, 200) },
    toggle() { this.pinned = ! this.pinned; this.hovering = false; if (this.pinned) this.announce() },
    close() { clearTimeout(this.timer); this.pinned = false; this.hovering = false },
    dismiss() {
      if (! this.open) return;
      const focusWasInside = this.$root.contains(document.activeElement);
      this.close();
      if (focusWasInside) this.$refs.trigger.focus();
    },
  }"
  x-id="['help', 'help-title']"
  @mouseenter="enter()"
  @mouseleave="leave()"
  @mousedown.outside="close()"
  @focusout="if ($event.relatedTarget && ! $root.contains($event.relatedTarget)) close()"
  @keydown.escape.window="dismiss()"
  @help-opened.window="if ($event.detail.id !== $id('help')) close()"
  {{ $attributes->class(['relative inline-flex align-middle leading-none']) }}
>
  <button
    type="button"
    x-ref="trigger"
    @click="toggle()"
    aria-label="{{ $label }}"
    :aria-expanded="open ? 'true' : 'false'"
    :aria-controls="$id('help')"
    :class="open ? 'border-primary bg-primary text-on-primary' : 'border-hairline-strong bg-canvas text-muted hover:bg-hover hover:text-ink'"
    class="relative flex size-4 cursor-pointer items-center justify-center rounded-full border text-2xs leading-none font-bold transition-colors duration-(--duration-fast) after:absolute after:-inset-1 after:content-[''] focus-ring"
  >?</button>

  <div
    x-cloak
    x-show="open"
    x-transition:enter="transition ease-out duration-(--duration-fast)"
    x-transition:enter-start="opacity-0"
    x-transition:leave="transition ease-out duration-(--duration-fast)"
    x-transition:leave-end="opacity-0"
    role="group"
    :id="$id('help')"
    :aria-labelledby="$id('help-title')"
    class="z-70 text-start leading-normal max-sm:fixed max-sm:inset-x-3 max-sm:top-auto max-sm:bottom-3 max-sm:w-auto max-sm:max-w-none sm:absolute sm:top-full sm:w-85 sm:max-w-[calc(100vw-2rem)] sm:pt-2.5 {{ $align === 'right' ? 'sm:-end-4' : 'sm:-start-4' }}"
  >
    <div class="absolute top-1.5 z-10 size-2.5 rotate-45 border-t border-s border-hairline bg-canvas max-sm:hidden {{ $align === 'right' ? 'end-5' : 'start-5' }}" aria-hidden="true"></div>

    <div class="relative overflow-hidden rounded-lg border border-hairline bg-canvas shadow-pop">
      <div class="flex items-start gap-3 px-4 pt-3.5 pb-3.5">
        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
          <span :id="$id('help-title')" class="text-sm font-semibold text-ink">{{ $title }}</span>

          <div class="text-sm leading-relaxed text-pretty text-muted">{{ $slot }}</div>
        </div>

        <button
          type="button"
          x-show="pinned"
          @click="dismiss()"
          aria-label="{{ __('Close') }}"
          class="-mt-1 -me-2 flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted transition-colors hover:text-ink focus-ring"
        >
          <x-icon name="close" :size="13" :stroke-width="1.5" />
        </button>
      </div>

      @isset($example)
        <div class="mx-4 mb-3.5 flex flex-col gap-1.5 rounded-md border border-hairline px-3 py-2.5">
          <x-caption tone="muted">{{ __('For example') }}</x-caption>

          <div class="text-sm leading-relaxed text-pretty text-body">{{ $example }}</div>
        </div>
      @endisset

      @if ($guideUrl)
        <div class="flex justify-end border-t border-hairline bg-card p-2">
          <x-button.secondary size="small" :href="$guideUrl" target="_blank" rel="noopener" data-turbo="false">
            {{ __('Read the guide') }}<span class="sr-only"> {{ __('(opens in a new tab)') }}</span>
            <x-icon name="arrow-right" :size="14" />
          </x-button.secondary>
        </div>
      @endif
    </div>
  </div>
</span>
