{{--
  A titled panel. The title, the description and the additional information can be
  given either as an attribute or as a named slot. Attributes land on the panel
  itself, not on the wrapper, so <x-box class="text-center"> styles the content.

  The title is a plain escaped string, so the "?" that explains the box cannot
  ride inside it. It goes in the `help` slot instead, and lands beside the title.

  A panel filled with <x-box.row> often needs one last line that is not a row:
  the link that loads the next page, or the one that leads to the full list. It
  goes in the `footer` slot, which draws it centred at the foot of the panel and
  takes the bottom rounding off the last row's hands. The slot passes its own
  attributes through, so a caller can put an id on it and aim ajax at it.

  @var string|null $title
  @var string|null $description
  @var string|null $additionalInfo
  @var string $padding
--}}
@props([
  'title' => null,
  'padding' => 'p-4 sm:p-4',
  'description' => null,
  'additionalInfo' => null,
])

<div class="space-y-2">
  @isset($title)
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <h2 class="text-lg font-semibold text-ink">{{ $title }}</h2>

        @isset($help)
          {{ $help }}
        @endisset
      </div>

      @isset($actions)
        <div>{{ $actions }}</div>
      @endisset
    </div>
  @endisset

  @isset($description)
    <div class="space-y-2 text-sm text-muted">
      {{ $description }}
    </div>
  @endisset

  @isset($additionalInfo)
    {{ $additionalInfo }}
  @endisset

  <div {{ $attributes->merge(['class' => 'rounded-xl border border-hairline bg-canvas ' . $padding]) }}>
    {{ $slot }}

    @isset($footer)
      <div {{ $footer->attributes->class(['rounded-b-xl px-4 py-3 text-center text-sm']) }}>
        {{ $footer }}
      </div>
    @endisset
  </div>
</div>
