{{--
  What a list shows when it holds nothing. A glyph in a soft disc, a short
  title, and a sentence saying why the list is empty rather than merely that it
  is: somebody who has just arrived learns what will eventually land here.

  The panel keeps a minimum height, so the screen does not jump the moment the
  first row arrives.

  The link at the foot is optional and points at the documentation. There is no
  documentation portal yet, so nothing passes it today. Nothing else belongs
  there: the person cannot create a log or send themselves an email, so a call
  to action would be a dead end.

  The glyph is the caller's, given as the `icon` slot, drawn larger than the one
  on the rows of the list it stands in for.

  Usage:
    <x-empty-state :title="__('No activity yet')">
      <x-slot:icon><svg ...></svg></x-slot:icon>
      {{ __('Why this is empty.') }}
    </x-empty-state>

  @var string|null $title
  @var string|null $href
  @var string|null $linkLabel
--}}
@props([
  'title' => null,
  'href' => null,
  'linkLabel' => null,
])

<div {{ $attributes->class(['flex min-h-65 flex-col items-center justify-center gap-3.5 px-8 py-11 text-center']) }}>
  @isset($icon)
    <span class="flex size-13 items-center justify-center rounded-full border border-hairline bg-sunken text-placeholder" aria-hidden="true">
      {{ $icon }}
    </span>
  @endisset

  <div class="max-w-90 space-y-1.5">
    @isset($title)
      <p class="text-sm font-semibold text-ink">{{ $title }}</p>
    @endisset

    <p class="text-sm leading-relaxed text-muted">{{ $slot }}</p>
  </div>

  @if ($href && $linkLabel)
    <x-link :href="$href" class="text-sm text-brand decoration-brand/25">{{ $linkLabel }}</x-link>
  @endif
</div>
